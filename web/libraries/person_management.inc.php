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
 * @file $/web/libraries/person_management.inc.php
 * @author Stephan Kreutzer
 * @since 2016-07-25
 */



require_once(dirname(__FILE__)."/database.inc.php");



define("PERSON_STATUS_UNKNOWN", 0);
define("PERSON_STATUS_ACTIVE", 1);
define("PERSON_STATUS_TRASHED", 2);



function GetPersons()
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

    if (logEvent(EVENT_READ, "GetPersons", array()) != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    $persons = Database::Get()->QueryUnsecure("SELECT `id`,\n".
                                              "    `family_name`,\n".
                                              "    `given_name`,\n".
                                              "    `date_of_birth`,\n".
                                              "    `location`,\n".
                                              "    `nationality`,\n".
                                              "    `status`,\n".
                                              "    `datetime_created`,\n".
                                              "    `datetime_modified`\n".
                                              "FROM `".Database::Get()->GetPrefix()."persons`\n".
                                              "WHERE 1");

    if (is_array($persons) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -5;
    }

    return $persons;
}


function GetPersonById($id)
{
    if (is_numeric($id) !== true)
    {
        return -1;
    }

    if (Database::Get()->IsConnected() !== true)
    {
        return -2;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -3;
    }

    if (logEvent(EVENT_READ, "GetPersonById", array($id)) != 0)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -5;
    }

    $person = Database::Get()->Query("SELECT `id`,\n".
                                     "    `family_name`,\n".
                                     "    `given_name`,\n".
                                     "    `date_of_birth`,\n".
                                     "    `location`,\n".
                                     "    `nationality`,\n".
                                     "    `status`,\n".
                                     "    `datetime_created`,\n".
                                     "    `datetime_modified`\n".
                                     "FROM `".Database::Get()->GetPrefix()."persons`\n".
                                     "WHERE `id`=?",
                                     array($id),
                                     array(Database::TYPE_INT));

    if (is_array($person) !== true)
    {
        return -6;
    }

    if (empty($person) == true)
    {
        return -7;
    }

    return $person[0];
}

function GetPersonsByIds(array $ids)
{
    if (is_array($ids) !== true)
    {
        return -1;
    }

    if (empty($ids))
    {
        return 1;
    }

    $idString = "";
    $whereClause = "";
    $idList = array();
    $typeList = array();

    foreach ($ids as $id)
    {
        if (!empty($idString))
        {
            $idString .= ", ";
        }

        $idString .= (int)$id;

        if (!empty($whereClause))
        {
            $whereClause .= " OR ";
        }

        $whereClause .= "`id`=?";

        $idList[] = (int)$id;
        $typeList[] = Database::TYPE_INT;
    }

    if (Database::Get()->IsConnected() !== true)
    {
        return -2;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -3;
    }

    if (logEvent(EVENT_READ, "GetPersonsByIds", array($idString)) != 0)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -5;
    }

    $persons = Database::Get()->Query("SELECT `id`,\n".
                                      "    `family_name`,\n".
                                      "    `given_name`,\n".
                                      "    `date_of_birth`,\n".
                                      "    `location`,\n".
                                      "    `nationality`,\n".
                                      "    `status`,\n".
                                      "    `datetime_created`,\n".
                                      "    `datetime_modified`\n".
                                      "FROM `".Database::Get()->GetPrefix()."persons`\n".
                                      "WHERE ".$whereClause,
                                      $idList,
                                      $typeList);

    if (is_array($persons) !== true)
    {
        return -6;
    }

    if (empty($persons) == true)
    {
        return -7;
    }

    return $persons;
}

function InsertNewPerson($familyName, $givenName, $dateOfBirth, $location, $nationality)
{
    /** @todo Check for empty parameters. */

    $nationality = (int)$nationality;

    require_once(dirname(__FILE__)."/../custom/nationality.inc.php");

    if ($nationality < 0 || $nationality > count(GetNationalityDefinitions()) - 1)
    {
        return -1;
    }

    if (Database::Get()->IsConnected() !== true)
    {
        return -2;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -3;
    }

    if (logEvent(EVENT_CREATE, "InsertNewPerson", array("\"".$familyName."\"", "\"".$givenName."\"", $dateOfBirth, "\"".$location."\"", ((int)$nationality))) != 0)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    $id = Database::Get()->Insert("INSERT INTO `".Database::Get()->GetPrefix()."persons` (`id`,\n".
                                  "    `family_name`,\n".
                                  "    `given_name`,\n".
                                  "    `date_of_birth`,\n".
                                  "    `location`,\n".
                                  "    `nationality`,\n".
                                  "    `status`,\n".
                                  "    `datetime_created`,\n".
                                  "    `datetime_modified`)\n".
                                  "VALUES (?, ?, ?, ?, ?, ?, ".PERSON_STATUS_ACTIVE.", NOW(), NOW())\n",
                                  array(NULL, $familyName, $givenName, $dateOfBirth, $location, $nationality),
                                  array(Database::TYPE_NULL, Database::TYPE_STRING, Database::TYPE_STRING, Database::TYPE_STRING, Database::TYPE_STRING, Database::TYPE_INT));

    if ($id <= 0)
    {
        Database::Get()->RollbackTransaction();
        return -5;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -6;
    }

    return $id;
}

function DeletePerson($personId)
{
    $personId = (int)$personId;

    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (logEvent(EVENT_DELETE, "DeletePerson", array($personId)) != 0)
    {
        Database::Get()->RollbackTransaction();
        return -3;
    }

    if (Database::Get()->Execute("UPDATE `".Database::Get()->GetPrefix()."persons`\n".
                                 "SET `status`=?,\n".
                                 "    `datetime_modified`=NOW()\n".
                                 "WHERE `id`=?",
                                 array(PERSON_STATUS_TRASHED, $personId),
                                 array(Database::TYPE_INT, Database::TYPE_INT)) !== true)
    {
        Database::Get()->RollbackTransaction();
        return -4;
    }

    require_once(dirname(__FILE__)."/note_management.inc.php");

    if (DeleteAllNotes($personId, false) !== 0)
    {
        Database::Get()->RollbackTransaction();
        return -5;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -6;
    }

    require_once(dirname(__FILE__)."/subscription_management.inc.php");
    SendSubscribedNotification(EVENT_DELETE, "DeletePerson", $personId);

    return 0;
}



?>
