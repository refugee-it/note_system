<?php
/* Copyright (C) 2012-2017  Stephan Kreutzer
 *
 * This file is part of note system for refugee-it.de.
 *
 * note system for refugee-it.de is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3 or any later version,
 * as published by the Free Software Foundation.
 *
 * note system for refugee-it.de is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License 3 for more details.
 *
 * You should have received a copy of the GNU Affero General Public License 3
 * along with note system for refugee-it.de. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * @file $/web/libraries/note_management.inc.php
 * @author Stephan Kreutzer
 * @since 2016-12-03
 */



require_once(dirname(__FILE__)."/database.inc.php");



define("NOTE_STATUS_UNKNOWN", 0);
define("NOTE_STATUS_ACTIVE", 1);
define("NOTE_STATUS_TRASHED", 2);
define("NOTE_STATUS_COMPLETED", 3);

define("NOTE_FLAGS_NONE", 0x0);
define("NOTE_FLAGS_INFORMATIVE", 0x1);
define("NOTE_FLAGS_NEEDINFORMATION", 0x2);
define("NOTE_FLAGS_NEEDACTION", 0x4);
define("NOTE_FLAGS_URGENT", 0x8);

define("NOTE_UPLOAD_STATUS_UNKNOWN", 0);
define("NOTE_UPLOAD_STATUS_PUBLIC", 1);
define("NOTE_UPLOAD_STATUS_TRASHED", 2);



function GetNotes($personId)
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    if (logEvent("GetNotes(".$personId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    /**
     * @todo Find out, if both id_user as well as id_user_assigned can be resolved
     *     to user.name with via SQL statement.
     */
    $notes = Database::Get()->Query("SELECT `id`,\n".
                                    "    `category`,\n".
                                    "    `text`,\n".
                                    "    `priority`,\n".
                                    "    `status`,\n".
                                    "    `flags`,\n".
                                    "    `datetime_created`,\n".
                                    "    `datetime_modified`,\n".
                                    "    `id_user_assigned`,\n".
                                    "    `id_user`\n".
                                    "FROM `".Database::Get()->GetPrefix()."notes`\n".
                                    "WHERE `id_person`=?\n".
                                    "ORDER BY `priority` DESC,\n".
                                    "    `datetime_created` DESC\n",
                                    array($personId),
                                    array(Database::TYPE_INT));

    if (is_array($notes) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -5;
    }

    require_once(dirname(__FILE__)."/user_management.inc.php");

    $users = GetUsers();
    $userNames = array();

    if (is_array($users) === true)
    {
        $userNames = array();

        foreach ($users as $user)
        {
            $userNames[(int)$user['id']] = $user['name'];
        }
    }

    foreach ($notes as &$note)
    {
        $userOwnerName = "";
        $userAssignedName = "";

        if ($note['id_user'] != 0 &&
            !empty($userNames))
        {
            if (array_key_exists($note['id_user'], $userNames) === true)
            {
                    $userOwnerName = $userNames[(int)$note['id_user']];
            }
        }

        if ($note['id_user_assigned'] != 0 &&
            !empty($userNames))
        {
            if (array_key_exists($note['id_user_assigned'], $userNames) === true)
            {
                $userAssignedName = $userNames[(int)$note['id_user_assigned']];
            }
        }

        $note['user_name'] = $userOwnerName;
        $note['user_assigned_name'] = $userAssignedName;
    }

    return $notes;
}

function GetNotesByAssignedUser($userId)
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    if (logEvent("GetNotesByAssignedUser(".$userId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    $notes = Database::Get()->Query("SELECT `id`,\n".
                                    "    `category`,\n".
                                    "    `text`,\n".
                                    "    `priority`,\n".
                                    "    `status`,\n".
                                    "    `flags`,\n".
                                    "    `datetime_created`,\n".
                                    "    `datetime_modified`,\n".
                                    "    `id_person`,\n".
                                    "    `id_user`\n".
                                    "FROM `".Database::Get()->GetPrefix()."notes`\n".
                                    "WHERE `id_user_assigned`=?\n".
                                    "ORDER BY `priority` DESC,\n".
                                    "    `datetime_created` DESC\n",
                                    array($userId),
                                    array(Database::TYPE_INT));

    if (is_array($notes) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -5;
    }

    return $notes;
}

function AddNote($personId, $category, $priority, $flags, $text, $userId)
{
    /** @todo Check for empty parameters. */

    $personId = (int)$personId;
    $category = (int)$category;
    $priority = (int)$priority;
    $flags = (int)$flags;

    $flagsMax = NOTE_FLAGS_INFORMATIVE |
                NOTE_FLAGS_NEEDINFORMATION |
                NOTE_FLAGS_NEEDACTION |
                NOTE_FLAGS_URGENT;

    if ($flags < NOTE_FLAGS_NONE ||
        $flags > $flagsMax)
    {
        return -6;
    }

    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    $notesStats = null;

    if (($flags & NOTE_FLAGS_NEEDINFORMATION) === NOTE_FLAGS_NEEDINFORMATION ||
        ($flags & NOTE_FLAGS_NEEDACTION) === NOTE_FLAGS_NEEDACTION)
    {
        $notesStats = GetNotesStatsById($personId);

        if (!is_array($notesStats) &&
            (int)$notesStats !== 1)
        {
            return -7;
        }
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    if (logEvent("AddNote(".$personId.", ".$category.", ".$priority.", ".$flags.", '".$text."').") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    $id = Database::Get()->Insert("INSERT INTO `".Database::Get()->GetPrefix()."notes` (`id`,\n".
                                  "    `category`,\n".
                                  "    `text`,\n".
                                  "    `priority`,\n".
                                  "    `status`,\n".
                                  "    `flags`,\n".
                                  "    `datetime_created`,\n".
                                  "    `datetime_modified`,\n".
                                  "    `id_user_assigned`,\n".
                                  "    `id_person`,\n".
                                  "    `id_user`)\n".
                                  "VALUES (?, ?, ?, ?, ".NOTE_STATUS_ACTIVE.", ?, NOW(), NOW(), ?, ?, ?)\n",
                                  array(NULL, $category, $text, $priority, $flags, NULL, $personId, $userId),
                                  array(Database::TYPE_NULL, Database::TYPE_INT, Database::TYPE_STRING, Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_NULL, Database::TYPE_INT, Database::TYPE_INT));

    if ($id <= 0)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if ($notesStats != null)
    {
        $flagNeedinformationCount = 0;
        $flagNeedactionCount = 0;
        $flagUrgentCount = 0;

        if (is_array($notesStats) === true)
        {
            if ((int)$notesStats['flag_needinformation'] > 0)
            {
                $flagNeedinformationCount = (int)$notesStats['flag_needinformation'];
            }

            if ((int)$notesStats['flag_needaction'] > 0)
            {
                $flagNeedactionCount = (int)$notesStats['flag_needaction'];
            }

            if ((int)$notesStats['flag_urgent'] > 0)
            {
                $flagUrgentCount = (int)$notesStats['flag_urgent'];
            }
        }

        if (($flags & NOTE_FLAGS_NEEDINFORMATION) === NOTE_FLAGS_NEEDINFORMATION)
        {
            $flagNeedinformationCount += 1;
        }

        if (($flags & NOTE_FLAGS_NEEDACTION) === NOTE_FLAGS_NEEDACTION)
        {
            $flagNeedactionCount += 1;
        }

        if (($flags & NOTE_FLAGS_URGENT) === NOTE_FLAGS_URGENT)
        {
            $flagUrgentCount += 1;
        }

        if (is_array($notesStats) === true)
        {
            $success = Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes_stats`\n".
                                                "SET `flag_needinformation`=?,\n".
                                                "    `flag_needaction`=?,\n".
                                                "    `flag_urgent`=?\n".
                                                "WHERE `id_person`=?",
                                                array($flagNeedinformationCount, $flagNeedactionCount, $flagUrgentCount, $personId),
                                                array(Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT));

            if ($success !== true)
            {
                Database::Get()->RollbackTransaction();
                return -8;
            }
        }
        else if ((int)$notesStats === 1)
        {
            $success = Database::Get()->Insert("INSERT INTO `".Database::Get()->GetPrefix()."notes_stats` (`id_person`,\n".
                                               "    `flag_needinformation`,\n".
                                               "    `flag_needaction`,\n".
                                               "    `flag_urgent`)\n".
                                               "VALUES (?, ?, ?, ?)\n",
                                               array($personId, $flagNeedinformationCount, $flagNeedactionCount, $flagUrgentCount),
                                               array(Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT));

            if ($success !== true)
            {
                Database::Get()->RollbackTransaction();
                return -9;
            }
        }
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -5;
    }

    return $id;
}

/**
 * @param[in] $userAssignedId Provide ID of the assigned user (0 for no assigned user)
 *     for optimization, null otherwise.
 * @param[in] $flagsOld Provide old flags for optimization, null otherwise.
 */
function UpdateNote($noteId, $personId, $categoryId, $priority, $flags, $userAssignedId, $flagsOld, $text)
{
    /** @todo Check for empty parameters. */

    $noteId = (int)$noteId;
    $personId = (int)$personId;
    $categoryId = (int)$categoryId;
    $priority = (int)$priority;
    $flags = (int)$flags;

    if ($userAssignedId !== null)
    {
        $userAssignedId = (int)$userAssignedId;
    }

    if ($flagsOld !== null)
    {
        $flagsOld = (int)$flagsOld;
    }

    $flagsMax = NOTE_FLAGS_INFORMATIVE |
                NOTE_FLAGS_NEEDINFORMATION |
                NOTE_FLAGS_NEEDACTION |
                NOTE_FLAGS_URGENT;

    if ($flags < NOTE_FLAGS_NONE ||
        $flags > $flagsMax)
    {
        return -1;
    }

    if (Database::Get()->IsConnected() !== true)
    {
        return -2;
    }

    if ($userAssignedId === null ||
        $flagsOld === null)
    {
        $flagsOld = Database::Get()->Query("SELECT `id_user_assigned`,\n".
                                           "    `flags`\n".
                                           "FROM `".Database::Get()->GetPrefix()."notes`\n".
                                           "WHERE `id`=?\n",
                                           array($noteId),
                                           array(Database::TYPE_INT));

        if (is_array($flagsOld) !== true)
        {
            return -3;
        }

        if (empty($flagsOld) == true)
        {
            return -4;
        }

        $userAssignedId = (int)$flagsOld[0]['id_user_assigned'];
        $flagsOld = (int)$flagsOld[0]['flags'];
    }

    $notesStats = null;

    // Updating flags statistic is only relevant if no user is assigned.
    if ($userAssignedId <= 0)
    {
        if (($flagsOld & NOTE_FLAGS_NEEDINFORMATION) === NOTE_FLAGS_NEEDINFORMATION ||
            ($flagsOld & NOTE_FLAGS_NEEDACTION) === NOTE_FLAGS_NEEDACTION ||
            ($flags & NOTE_FLAGS_NEEDINFORMATION) === NOTE_FLAGS_NEEDINFORMATION ||
            ($flags & NOTE_FLAGS_NEEDACTION) === NOTE_FLAGS_NEEDACTION)
        {
            $notesStats = GetNotesStatsById($personId);

            if (!is_array($notesStats) &&
                (int)$notesStats !== 1)
            {
                return -5;
            }
        }
    }


    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -6;
    }

    if (logEvent("UpdateNote(".$noteId.", ".$personId.", ".$categoryId.", ".$priority.", ".$flags.", '".$text."').") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -7;
    }

    $success = Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes`\n".
                                        "SET `category`=?,\n".
                                        "    `text`=?,\n".
                                        "    `priority`=?,\n".
                                        "    `flags`=?,\n".
                                        "    `datetime_modified`=NOW()\n".
                                        "WHERE `id`=?",
                                        array($categoryId, $text, $priority, $flags, $noteId),
                                        array(Database::TYPE_INT, Database::TYPE_STRING, Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT));

    if ($success !== true)
    {
        Database::Get()->RollbackTransaction();
        return -8;
    }

    if ($notesStats != null)
    {
        $flagNeedinformationDiff = 0;
        $flagNeedactionDiff = 0;
        $flagUrgentDiff = 0;

        if (($flagsOld & NOTE_FLAGS_NEEDINFORMATION) === NOTE_FLAGS_NEEDINFORMATION &&
            ($flags & NOTE_FLAGS_NEEDINFORMATION) !== NOTE_FLAGS_NEEDINFORMATION)
        {
            $flagNeedinformationDiff -= 1;
        }

        if (($flagsOld & NOTE_FLAGS_NEEDINFORMATION) !== NOTE_FLAGS_NEEDINFORMATION &&
            ($flags & NOTE_FLAGS_NEEDINFORMATION) === NOTE_FLAGS_NEEDINFORMATION)
        {
            $flagNeedinformationDiff += 1;
        }

        if (($flagsOld & NOTE_FLAGS_NEEDACTION) === NOTE_FLAGS_NEEDACTION &&
            ($flags & NOTE_FLAGS_NEEDACTION) !== NOTE_FLAGS_NEEDACTION)
        {
            $flagNeedactionDiff -= 1;
        }

        if (($flagsOld & NOTE_FLAGS_NEEDACTION) !== NOTE_FLAGS_NEEDACTION &&
            ($flags & NOTE_FLAGS_NEEDACTION) === NOTE_FLAGS_NEEDACTION)
        {
            $flagNeedactionDiff += 1;
        }

        if (($flagsOld & NOTE_FLAGS_URGENT) === NOTE_FLAGS_URGENT &&
            ($flags & NOTE_FLAGS_URGENT) !== NOTE_FLAGS_URGENT)
        {
            $flagUrgentDiff -= 1;
        }

        if (($flagsOld & NOTE_FLAGS_URGENT) !== NOTE_FLAGS_URGENT &&
            ($flags & NOTE_FLAGS_URGENT) === NOTE_FLAGS_URGENT)
        {
            $flagUrgentDiff += 1;
        }

        if ($flagNeedinformationDiff != 0 ||
            $flagNeedactionDiff != 0 ||
            $flagUrgentDiff != 0)
        {
            $flagNeedinformationCount = 0;
            $flagNeedactionCount = 0;
            $flagUrgentCount = 0;

            if (is_array($notesStats) === true)
            {
                if ((int)$notesStats['flag_needinformation'] > 0)
                {
                    $flagNeedinformationCount = (int)$notesStats['flag_needinformation'];
                }

                if ((int)$notesStats['flag_needaction'] > 0)
                {
                    $flagNeedactionCount = (int)$notesStats['flag_needaction'];
                }

                if ((int)$notesStats['flag_urgent'] > 0)
                {
                    $flagUrgentCount = (int)$notesStats['flag_urgent'];
                }
            }
            else if ((int)$notesStats === 1)
            {
                if ($flagUrgentDiff === 0 &&
                    ($flagsOld & NOTE_FLAGS_URGENT) === NOTE_FLAGS_URGENT &&
                    ($flags & NOTE_FLAGS_URGENT) === NOTE_FLAGS_URGENT)
                {
                    // If urgent flag is set, but a statistical flag entry doesn't
                    // exist because neither needinformation nor needaction flags
                    // were set previously (might get set with this function call),
                    // the urgent flag needs its representation in the statistical
                    // entry that might get created below.
                    $flagUrgentCount = 1;
                }
            }

            $flagNeedinformationCount += $flagNeedinformationDiff;
            $flagNeedactionCount += $flagNeedactionDiff;
            $flagUrgentCount += $flagUrgentDiff;

            if ($flagNeedinformationCount > 0 ||
                $flagNeedactionCount > 0)
            {
                if (is_array($notesStats) === true)
                {
                    $success = Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes_stats`\n".
                                                        "SET `flag_needinformation`=?,\n".
                                                        "    `flag_needaction`=?,\n".
                                                        "    `flag_urgent`=?\n".
                                                        "WHERE `id_person`=?",
                                                        array($flagNeedinformationCount, $flagNeedactionCount, $flagUrgentCount, $personId),
                                                        array(Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT));

                    if ($success !== true)
                    {
                        Database::Get()->RollbackTransaction();
                        return -9;
                    }
                }
                else
                {
                    $success = Database::Get()->Insert("INSERT INTO `".Database::Get()->GetPrefix()."notes_stats` (`id_person`,\n".
                                                       "    `flag_needinformation`,\n".
                                                       "    `flag_needaction`,\n".
                                                       "    `flag_urgent`)\n".
                                                       "VALUES (?, ?, ?, ?)\n",
                                                       array($personId, $flagNeedinformationCount, $flagNeedactionCount, $flagUrgentCount),
                                                       array(Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT));

                    if ($success !== true)
                    {
                        Database::Get()->RollbackTransaction();
                        return -10;
                    }
                }
            }
            else
            {
                if (is_array($notesStats) === true)
                {
                    $success = Database::Get()->Execute("DELETE\n".
                                                        "FROM `".Database::Get()->GetPrefix()."notes_stats`\n".
                                                        "WHERE `id_person`=?",
                                                        array($personId),
                                                        array(Database::TYPE_INT));

                    if ($success !== true)
                    {
                        Database::Get()->RollbackTransaction();
                        return -11;
                    }
                }
                else
                {
                    // The code above assumes that something needs to be done, but the new statistical
                    // flag counts are 0 and there's no entry present...
                    Database::Get()->RollbackTransaction();
                    return -12;
                }
            }
        }
    }

    if ($userAssignedId > 0 &&
        ($flags & NOTE_FLAGS_NEEDINFORMATION) !== NOTE_FLAGS_NEEDINFORMATION &&
        ($flags & NOTE_FLAGS_NEEDACTION) !== NOTE_FLAGS_NEEDACTION)
    {
        if (NoteDeAssignUser($noteId, $userAssignedId) !== 0)
        {
            Database::Get()->RollbackTransaction();
            return -14;
        }
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -13;
    }

    return true;
}

function GetNoteById($noteId)
{
    $noteId = (int)$noteId;

    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    if (logEvent("GetNoteById(".$noteId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    /**
     * @todo Find out, if both id_user as well as id_user_assigned can be resolved
     *     to user.name with via SQL statement.
     */
    $note = Database::Get()->Query("SELECT `id`,\n".
                                   "    `category`,\n".
                                   "    `text`,\n".
                                   "    `priority`,\n".
                                   "    `status`,\n".
                                   "    `flags`,\n".
                                   "    `datetime_created`,\n".
                                   "    `datetime_modified`,\n".
                                   "    `id_user_assigned`,\n".
                                   "    `id_person`,\n".
                                   "    `id_user`\n".
                                   "FROM `".Database::Get()->GetPrefix()."notes`\n".
                                   "WHERE `id`=?\n",
                                   array($noteId),
                                   array(Database::TYPE_INT));

    if (is_array($note) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (empty($note) == true)
    {
        Database::Get()->RollbackTransaction();
        return -5;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -6;
    }

    $note = $note[0];

    require_once(dirname(__FILE__)."/user_management.inc.php");

    $users = GetUsers();
    $userNames = array();

    if (is_array($users) === true)
    {
        $userNames = array();

        foreach ($users as $user)
        {
            $userNames[(int)$user['id']] = $user['name'];
        }
    }

    {
        $userOwnerName = "";
        $userAssignedName = "";

        if ($note['id_user'] != 0 &&
            !empty($userNames))
        {
            if (array_key_exists($note['id_user'], $userNames) === true)
            {
                    $userOwnerName = $userNames[(int)$note['id_user']];
            }
        }

        if ($note['id_user_assigned'] != 0 &&
            !empty($userNames))
        {
            if (array_key_exists($note['id_user_assigned'], $userNames) === true)
            {
                $userAssignedName = $userNames[(int)$note['id_user_assigned']];
            }
        }

        $note['user_name'] = $userOwnerName;
        $note['user_assigned_name'] = $userAssignedName;
    }

    return $note;
}

function NoteAssignUser($noteId, $userId)
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    $note = Database::Get()->Query("SELECT `id`,\n".
                                   "    `status`,\n".
                                   "    `flags`,\n".
                                   "    `id_person`,\n".
                                   "    `id_user_assigned`\n".
                                   "FROM `".Database::Get()->GetPrefix()."notes`\n".
                                   "WHERE `id`=?\n",
                                   array($noteId),
                                   array(Database::TYPE_INT));

    if (is_array($note) !== true)
    {
        return -2;
    }

    if (empty($note) == true)
    {
        return -3;
    }

    $note = $note[0];

    if ((int)$note['status'] !== NOTE_STATUS_ACTIVE)
    {
        return -4;
    }

    if (is_numeric($note['id_user_assigned']) == true)
    {
        return 1;
    }

    $personId = (int)$note['id_person'];

    $notesStats = null;
    $flags = (int)$note['flags'];

    $flagNeedinformationSubtract = false;
    $flagNeedactionSubtract = false;
    $flagUrgentSubtract = false;

    if (($flags & NOTE_FLAGS_NEEDINFORMATION) === NOTE_FLAGS_NEEDINFORMATION)
    {
        $flagNeedinformationSubtract = true;
    }

    if (($flags & NOTE_FLAGS_NEEDACTION) === NOTE_FLAGS_NEEDACTION)
    {
        $flagNeedactionSubtract = true;
    }

    if (($flags & NOTE_FLAGS_URGENT) === NOTE_FLAGS_URGENT)
    {
        $flagUrgentSubtract = true;
    }

    if ($flagNeedinformationSubtract === true ||
        $flagNeedactionSubtract)
    {
        $notesStats = GetNotesStatsById($personId);

        if (!is_array($notesStats) &&
            (int)$notesStats !== 1)
        {
            return -10;
        }
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -6;
    }

    if (logEvent("NoteAssignUser(".$personId.", ".$noteId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -7;
    }

    $result = Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes`\n".
                                       "SET `id_user_assigned`=".$userId.",\n".
                                       "    `datetime_modified`=NOW()\n".
                                       "WHERE `id`=?",
                                       array($noteId),
                                       array(Database::TYPE_INT));
    if ($result !== true)
    {
        Database::Get()->RollbackTransaction();
        return -8;
    }

    if (is_array($notesStats) === true)
    {
        $flagNeedinformationCount = 0;
        $flagNeedactionCount = 0;
        $flagUrgentCount = 0;

        if ((int)$notesStats['flag_needinformation'] > 0)
        {
            $flagNeedinformationCount = (int)$notesStats['flag_needinformation'];
        }

        if ((int)$notesStats['flag_needaction'] > 0)
        {
            $flagNeedactionCount = (int)$notesStats['flag_needaction'];
        }

        if ((int)$notesStats['flag_urgent'] > 0)
        {
            $flagUrgentCount = (int)$notesStats['flag_urgent'];
        }

        if ($flagNeedinformationSubtract === true)
        {
            $flagNeedinformationCount -= 1;
        }

        if ($flagNeedactionSubtract === true)
        {
            $flagNeedactionCount -= 1;
        }

        if ($flagUrgentSubtract === true)
        {
            $flagUrgentCount -= 1;
        }

        if ($flagNeedinformationCount > 0 ||
            $flagNeedactionCount > 0)
        {
            $success = Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes_stats`\n".
                                                "SET `flag_needinformation`=?,\n".
                                                "    `flag_needaction`=?,\n".
                                                "    `flag_urgent`=?\n".
                                                "WHERE `id_person`=?",
                                                array($flagNeedinformationCount, $flagNeedactionCount, $flagUrgentCount, $personId),
                                                array(Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT));

            if ($success !== true)
            {
                Database::Get()->RollbackTransaction();
                return -11;
            }
        }
        else
        {
            $success = Database::Get()->Execute("DELETE\n".
                                                "FROM `".Database::Get()->GetPrefix()."notes_stats`\n".
                                                "WHERE `id_person`=?",
                                                array($personId),
                                                array(Database::TYPE_INT));

            if ($success !== true)
            {
                Database::Get()->RollbackTransaction();
                return -12;
            }
        }
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -9;
    }

    return 0;
}

function NoteDeAssignUser($noteId, $userId)
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    $note = Database::Get()->Query("SELECT `id`,\n".
                                   "    `status`,\n".
                                   "    `flags`,\n".
                                   "    `id_person`,\n".
                                   "    `id_user_assigned`\n".
                                   "FROM `".Database::Get()->GetPrefix()."notes`\n".
                                   "WHERE `id`=?\n",
                                   array($noteId),
                                   array(Database::TYPE_INT));

    if (is_array($note) !== true)
    {
        return -2;
    }

    if (empty($note) == true)
    {
        return -3;
    }

    $note = $note[0];

    if ((int)$note['status'] !== NOTE_STATUS_ACTIVE)
    {
        return -4;
    }

    if (is_numeric($note['id_user_assigned']) != true)
    {
        return -5;
    }

    if ((int)$note['id_user_assigned'] != (int)$userId)
    {
        return -6;
    }

    $personId = (int)$note['id_person'];

    $notesStats = null;
    $flags = (int)$note['flags'];

    $flagNeedinformationAdd = false;
    $flagNeedactionAdd = false;
    $flagUrgentAdd = false;

    if (($flags & NOTE_FLAGS_NEEDINFORMATION) === NOTE_FLAGS_NEEDINFORMATION)
    {
        $flagNeedinformationAdd = true;
    }

    if (($flags & NOTE_FLAGS_NEEDACTION) === NOTE_FLAGS_NEEDACTION)
    {
        $flagNeedactionAdd = true;
    }

    if (($flags & NOTE_FLAGS_URGENT) === NOTE_FLAGS_URGENT)
    {
        $flagUrgentAdd = true;
    }

    if ($flagNeedinformationAdd === true ||
        $flagNeedactionAdd)
    {
        $notesStats = GetNotesStatsById($personId);

        if (!is_array($notesStats) &&
            (int)$notesStats !== 1)
        {
            return -11;
        }
    }

    $inTransaction = Database::Get()->IsInTransaction();

    if ($inTransaction == false)
    {
        if (Database::Get()->BeginTransaction() !== true)
        {
            return -7;
        }
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (logEvent("NoteDeAssignUser(".$personId.", ".$noteId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -8;
    }

    $result = Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes`\n".
                                       "SET `id_user_assigned`=NULL,\n".
                                       "    `datetime_modified`=NOW()\n".
                                       "WHERE `id`=?",
                                       array($noteId),
                                       array(Database::TYPE_INT));

    if ($result !== true)
    {
        Database::Get()->RollbackTransaction();
        return -9;
    }

    if ($notesStats != null)
    {
        $flagNeedinformationCount = 0;
        $flagNeedactionCount = 0;
        $flagUrgentCount = 0;

        if (is_array($notesStats) === true)
        {
            if ((int)$notesStats['flag_needinformation'] > 0)
            {
                $flagNeedinformationCount = (int)$notesStats['flag_needinformation'];
            }

            if ((int)$notesStats['flag_needaction'] > 0)
            {
                $flagNeedactionCount = (int)$notesStats['flag_needaction'];
            }

            if ((int)$notesStats['flag_urgent'] > 0)
            {
                $flagUrgentCount = (int)$notesStats['flag_urgent'];
            }
        }

        if ($flagNeedinformationAdd === true)
        {
            $flagNeedinformationCount += 1;
        }

        if ($flagNeedactionAdd === true)
        {
            $flagNeedactionCount += 1;
        }

        if ($flagUrgentAdd === true)
        {
            $flagUrgentCount += 1;
        }

        if (is_array($notesStats) === true)
        {
            $success = Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes_stats`\n".
                                                "SET `flag_needinformation`=?,\n".
                                                "    `flag_needaction`=?,\n".
                                                "    `flag_urgent`=?\n".
                                                "WHERE `id_person`=?",
                                                array($flagNeedinformationCount, $flagNeedactionCount, $flagUrgentCount, $personId),
                                                array(Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT));

            if ($success !== true)
            {
                Database::Get()->RollbackTransaction();
                return -12;
            }
        }
        else if ($notesStats === 1)
        {
           $success = Database::Get()->Insert("INSERT INTO `".Database::Get()->GetPrefix()."notes_stats` (`id_person`,\n".
                                               "    `flag_needinformation`,\n".
                                               "    `flag_needaction`,\n".
                                               "    `flag_urgent`)\n".
                                               "VALUES (?, ?, ?, ?)\n",
                                               array($personId, $flagNeedinformationCount, $flagNeedactionCount, $flagUrgentCount),
                                               array(Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT));

            if ($success !== true)
            {
                Database::Get()->RollbackTransaction();
                return -13;
            }
        }
    }

    if ($inTransaction == false)
    {
        if (Database::Get()->CommitTransaction() !== true)
        {
            return -10;
        }
    }

    return 0;
}

function NoteCompleted($noteId, $userId)
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    $note = Database::Get()->Query("SELECT `id`,\n".
                                   "    `status`,\n".
                                   "    `flags`,\n".
                                   "    `id_person`,\n".
                                   "    `id_user_assigned`\n".
                                   "FROM `".Database::Get()->GetPrefix()."notes`\n".
                                   "WHERE `id`=?\n",
                                   array($noteId),
                                   array(Database::TYPE_INT));

    if (is_array($note) !== true)
    {
        return -2;
    }

    if (empty($note) == true)
    {
        return -3;
    }

    $note = $note[0];

    if ((int)$note['status'] !== NOTE_STATUS_ACTIVE)
    {
        return -4;
    }

    if (is_numeric($note['id_user_assigned']) != true)
    {
        return -5;
    }

    if ((int)$note['id_user_assigned'] != (int)$userId)
    {
        return -6;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -7;
    }

    if (logEvent("NoteCompleted(".$noteId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -8;
    }

    $result = Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes`\n".
                                       "SET `priority`=1,\n".
                                       "    `flags`=".NOTE_FLAGS_INFORMATIVE.",\n".
                                       "    `datetime_modified`=NOW(),\n".
                                       "    `status`=".NOTE_STATUS_COMPLETED."\n".
                                       "WHERE `id`=?",
                                       array($noteId),
                                       array(Database::TYPE_INT));

    if ($result !== true)
    {
        Database::Get()->RollbackTransaction();
        return -9;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -10;
    }

    return 0;
}

function NoteAddUpload($noteId, $serverName, $displayName)
{
    /** @todo Check for empty parameters. */

    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    $internalName = md5(uniqid(rand(), true));

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    if (logEvent("NoteAddUpload(".$noteId.", '".$displayName."', '".$internalName."').") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    $id = Database::Get()->Insert("INSERT INTO `".Database::Get()->GetPrefix()."notes_uploads` (`id`,\n".
                                  "    `display_name`,\n".
                                  "    `internal_name`,\n".
                                  "    `status`,\n".
                                  "    `datetime_created`,\n".
                                  "    `id_note`)\n".
                                  "VALUES (?, ?, ?, ?, NOW(), ?)\n",
                                  array(NULL, $displayName, $internalName, NOTE_UPLOAD_STATUS_PUBLIC, $noteId),
                                  array(Database::TYPE_NULL, Database::TYPE_STRING, Database::TYPE_STRING, Database::TYPE_INT, Database::TYPE_INT));

    if ($id <= 0)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (@move_uploaded_file($serverName, "./notes_files/".$internalName) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -5;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        Database::Get()->RollbackTransaction();
        return -6;
    }

    return array("id" => $id,
                 "internal_name" => $internalName);
}

function GetNoteUploads($noteId)
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    if (logEvent("GetNoteUploads(".$noteId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    $uploads = Database::Get()->Query("SELECT `id`,\n".
                                      "    `display_name`,\n".
                                      "    `internal_name`,\n".
                                      "    `datetime_created`,\n".
                                      "    `status`\n".
                                      "FROM `".Database::Get()->GetPrefix()."notes_uploads`\n".
                                      "WHERE `id_note`=?\n".
                                      "ORDER BY `datetime_created` ASC\n",
                                      array($noteId),
                                      array(Database::TYPE_INT));

    if (is_array($uploads) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -5;
    }

    return $uploads;
}

function GetNoteFileById($fileId)
{
    $fileId = (int)$fileId;

    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    if (logEvent("GetNoteFileById(".$fileId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    $file = Database::Get()->Query("SELECT `id`,\n".
                                   "    `display_name`,\n".
                                   "    `internal_name`,\n".
                                   "    `status`,\n".
                                   "    `datetime_created`,\n".
                                   "    `id_note`\n".
                                   "FROM `".Database::Get()->GetPrefix()."notes_uploads`\n".
                                   "WHERE `id`=?\n",
                                   array($fileId),
                                   array(Database::TYPE_INT));

    if (is_array($file) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (empty($file) == true)
    {
        Database::Get()->RollbackTransaction();
        return -5;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -6;
    }

    return $file[0];
}

function NoteRemoveUpload($fileId)
{
    /** @todo Check for empty parameters. */

    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    if (logEvent("NoteRemoveUpload(".$fileId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    $success = Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes_uploads`\n".
                                        "SET `status`=?\n".
                                        "WHERE `id`=?",
                                        array(NOTE_UPLOAD_STATUS_TRASHED, $fileId),
                                        array(Database::TYPE_INT, Database::TYPE_INT));

    if ($success !== true)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        Database::Get()->RollbackTransaction();
        return -5;
    }

    return 0;
}

/**
 * @param[in] $status Provide the status of the note for optimization, null otherwise.
 * @param[in] $flags Provide flags for optimization, null otherwise.
 * @param[in] $userAssignedId Provide ID of the assigned user (0 for no assigned user)
 *     for optimization, null otherwise.
 * @param[in] $personId Provide the ID of the person the note is attached to for
 *     optimization, null otherwise.
 */
function DeleteNote($noteId, $status, $flags, $userAssignedId, $personId)
{
    $noteId = (int)$noteId;

    if ($status !== null)
    {
        $status = (int)$status;

        if ($status === NOTE_STATUS_TRASHED)
        {
            return 1;
        }
    }

    if ($flags !== null)
    {
        $flags = (int)$flags;
    }

    if ($userAssignedId !== null)
    {
        $userAssignedId = (int)$userAssignedId;
    }

    if ($personId !== null)
    {
        $personId = (int)$personId;
    }

    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    if ($status === null ||
        $flags === null ||
        $userAssignedId === null ||
        $personId === null)
    {
        $note = Database::Get()->Query("SELECT `status`,\n".
                                       "    `flags`,\n".
                                       "    `id_user_assigned`,\n".
                                       "    `id_person`\n".
                                       "FROM `".Database::Get()->GetPrefix()."notes`\n".
                                       "WHERE `id`=?\n",
                                       array($noteId),
                                       array(Database::TYPE_INT));

        if (is_array($note) !== true)
        {
            return -2;
        }

        if (empty($note) == true)
        {
            return -3;
        }

        if ((int)$note['status'] === NOTE_STATUS_TRASHED)
        {
            return 1;
        }

        $flags = (int)$note['flags'];
        $userAssignedId = (int)$note['id_user_assigned'];
        $personId = (int)$note['id_person'];
    }

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -4;
    }

    if ($userAssignedId <= 0)
    {
        $flagNeedinformationDiff = 0;
        $flagNeedactionDiff = 0;
        $flagUrgentDiff = 0;

        if (($flags & NOTE_FLAGS_NEEDINFORMATION) === NOTE_FLAGS_NEEDINFORMATION)
        {
            $flagNeedinformationDiff -= 1;
        }

        if (($flags & NOTE_FLAGS_NEEDACTION) === NOTE_FLAGS_NEEDACTION)
        {
            $flagNeedactionDiff -= 1;
        }

        if (($flags & NOTE_FLAGS_URGENT) === NOTE_FLAGS_URGENT)
        {
            $flagUrgentDiff -= 1;
        }

        if ($flagNeedinformationDiff < 0 ||
            $flagNeedactionDiff < 0)
        {
            $notesStats = GetNotesStatsById($personId);

            if (!is_array($notesStats) &&
                (int)$notesStats !== 1)
            {
                Database::Get()->RollbackTransaction();
                return -5;
            }

            if (is_array($notesStats) === true)
            {
                $flagNeedinformationCount = (int)$notesStats['flag_needinformation'];
                $flagNeedactionCount = (int)$notesStats['flag_needaction'];
                $flagUrgentCount = (int)$notesStats['flag_urgent'];

                $flagNeedinformationCount += $flagNeedinformationDiff;
                $flagNeedactionCount += $flagNeedactionDiff;
                $flagUrgentCount += $flagUrgentDiff;

                if ($flagNeedinformationCount > 0 ||
                    $flagNeedactionCount > 0)
                {
                    $success = Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes_stats`\n".
                                                        "SET `flag_needinformation`=?,\n".
                                                        "    `flag_needaction`=?,\n".
                                                        "    `flag_urgent`=?\n".
                                                        "WHERE `id_person`=?",
                                                        array($flagNeedinformationCount, $flagNeedactionCount, $flagUrgentCount, $personId),
                                                        array(Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT, Database::TYPE_INT));

                    if ($success !== true)
                    {
                        Database::Get()->RollbackTransaction();
                        return -6;
                    }
                }
                else
                {
                    $success = Database::Get()->Execute("DELETE\n".
                                                        "FROM `".Database::Get()->GetPrefix()."notes_stats`\n".
                                                        "WHERE `id_person`=?",
                                                        array($personId),
                                                        array(Database::TYPE_INT));

                    if ($success !== true)
                    {
                        Database::Get()->RollbackTransaction();
                        return -7;
                    }
                }
            }
        }
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (logEvent("DeleteNote(".$noteId.", ".$personId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -8;
    }

    $success = Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes`\n".
                                        "SET `status`=?,\n".
                                        "    `datetime_modified`=NOW()\n".
                                        "WHERE `id`=?",
                                        array(NOTE_STATUS_TRASHED, $noteId),
                                        array(Database::TYPE_INT, Database::TYPE_INT));

    if ($success !== true)
    {
        Database::Get()->RollbackTransaction();
        return -9;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -10;
    }

    return 0;
}

function DeleteAllNotes($personId)
{
    $personId = (int)$personId;

    $notes = Database::Get()->Query("SELECT `id`\n".
                                    "FROM `".Database::Get()->GetPrefix()."notes`\n".
                                    "WHERE `id_person`=?\n",
                                    array($personId),
                                    array(Database::TYPE_INT));

    if (is_array($notes) !== true)
    {
        return -1;
    }

    if (empty($notes))
    {
        return 0;
    }

    if (Database::Get()->IsConnected() !== true)
    {
        return -2;
    }

    $inTransaction = Database::Get()->IsInTransaction();

    if ($inTransaction == false)
    {
        if (Database::Get()->BeginTransaction() !== true)
        {
            return -3;
        }
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (logEvent("DeleteAllNotes(".$personId.").") != 0)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes`\n".
                                 "SET `status`=?,\n".
                                 "    `id_user_assigned`=?,\n".
                                 "    `datetime_modified`=NOW()\n".
                                 "WHERE `id_person`=?",
                                 array(NOTE_STATUS_TRASHED, null, $personId),
                                 array(Database::TYPE_INT, Database::TYPE_NULL, Database::TYPE_INT)) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -5;
    }

    $whereClause = "";
    $valueList = array(NOTE_STATUS_TRASHED);
    $typeList = array(Database::TYPE_INT);

    foreach ($notes as $note)
    {
        if (!empty($whereClause))
        {
            $whereClause .= " OR ";
        }

        $whereClause .= "`id_note`=?";
        $valueList[] = $note['id'];
        $typeList[] = Database::TYPE_INT;
    }

    if (Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."notes_uploads`\n".
                                 "SET `status`=?\n".
                                 "WHERE ".$whereClause,
                                 $valueList,
                                 $typeList) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -6;
    }

    if (Database::Get()->Execute("DELETE\n".
                                 "FROM `".Database::Get()->GetPrefix()."notes_stats`\n".
                                 "WHERE `id_person`=?",
                                 array($personId),
                                 array(Database::TYPE_INT)) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -7;
    }

    if ($inTransaction == false)
    {
        if (Database::Get()->CommitTransaction() !== true)
        {
            return -8;
        }
    }

    return 0;
}

function GetNotesStats()
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    $notesStats = Database::Get()->QueryUnsecure("SELECT `id_person`,\n".
                                                 "    `flag_needinformation`,\n".
                                                 "    `flag_needaction`,\n".
                                                 "    `flag_urgent`\n".
                                                 "FROM `".Database::Get()->GetPrefix()."notes_stats`\n".
                                                 "WHERE 1\n");

    if (is_array($notesStats) !== true)
    {
        return -2;
    }

    if (empty($notesStats) == true)
    {
        return 1;
    }

    $result = array();

    foreach ($notesStats as $stat)
    {
        $result[(int)$stat['id_person']] = $stat;
    }

    return $result;
}

function GetNotesStatsById($personId)
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    $notesStats = Database::Get()->Query("SELECT `flag_needinformation`,\n".
                                         "    `flag_needaction`,\n".
                                         "    `flag_urgent`\n".
                                         "FROM `".Database::Get()->GetPrefix()."notes_stats`\n".
                                         "WHERE `id_person`=?\n",
                                         array($personId),
                                         array(Database::TYPE_INT));

    if (is_array($notesStats) !== true)
    {
        return -2;
    }

    if (empty($notesStats) == true)
    {
        return 1;
    }

    $notesStats = $notesStats[0];

    return $notesStats;
}


?>
