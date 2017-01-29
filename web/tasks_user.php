<?php
/* Copyright (C) 2014-2017  Stephan Kreutzer
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
 * @file $/web/tasks_user.php
 * @brief Displays tasks assigned to the current user.
 * @details A task is a note which needs action.
 * @author Stephan Kreutzer
 * @since 2014-06-08
 */



require_once("./libraries/https.inc.php");

session_start();

if (isset($_SESSION['user_id']) !== true)
{
    header("HTTP/1.1 403 Forbidden");
    exit(-1);
}

require_once("./libraries/user_defines.inc.php");

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN &&
    (int)$_SESSION['user_role'] !== USER_ROLE_USER)
{
    header("HTTP/1.1 403 Forbidden");
    exit(-1);
}


require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("tasks_user"));
require_once("./libraries/note_management.inc.php");


echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
     "<!DOCTYPE html\n".
     "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
     "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
     "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"".getCurrentLanguage()."\" lang=\"".getCurrentLanguage()."\">\n".
     "    <head>\n".
     "        <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>\n".
     "        <title>".LANG_PAGETITLE."</title>\n".
     "        <link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"mainstyle.css\"/>\n".
     "        <link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"mainstyle_print.css\"/>\n".
     "        <style type=\"text/css\">\n".
     "          .th, .td\n".
     "          {\n".
     "              padding: 0px 10px 0px 0px;\n".
     "          }\n".
     "\n".
     "          .urgent\n".
     "          {\n".
     "              color: red;\n".
     "          }\n".
     "        </style>\n".
     "        <script type=\"text/javascript\" src=\"tsorter.js\"></script>\n".
     "        <script type=\"text/javascript\">\n".
     "          window.onload = function() {\n".
     "              tsorter.create('note_table', 0);\n".
     "          };\n".
     "        </script>\n".
     "    </head>\n".
     "    <body>\n".
     "        <div class=\"mainbox\">\n".
     "          <div class=\"mainbox_header\">\n".
     "            <h1 class=\"mainbox_header_h1\">".LANG_HEADER."</h1>\n".
     "          </div>\n".
     "          <div class=\"mainbox_body\">\n";

$notes = GetNotesByAssignedUser((int)$_SESSION['user_id']);

if (is_array($notes) === true)
{
    if (empty($notes) === false)
    {
        require_once("./libraries/note_category.inc.php");

        $categories = GetNoteCategoryDefinitions();
        $categoriesCached = array();

        if (is_array($categories) === true)
        {
            if (count($categories) > 0)
            {
                foreach ($categories as $category)
                {
                    $categoriesCached[$category->getId()] = $category->getName();
                }
            }
            else
            {
                $categories = null;
            }
        }
        else
        {
            $categories = null;
        }

        echo "            <table id=\"note_table\">\n".
             "              <thead>\n".
             "                <tr>\n".
             "                  <th tsorter:data-tsorter=\"numeric\">".LANG_NOTES_TABLECOLUMNCAPTION_PRIORITY."</th>\n".
             "                  <th tsorter:data-tsorter=\"default\">".LANG_NOTES_TABLECOLUMNCAPTION_CATEGORY."</th>\n".
             "                  <th tsorter:data-tsorter=\"date\">".LANG_NOTES_TABLECOLUMNCAPTION_MODIFIED."</th>\n".
             "                  <th tsorter:data-tsorter=\"numeric\">".LANG_NOTES_TABLECOLUMNCAPTION_DAYSSINCECREATION."</th>\n".
             "                  <th tsorter:data-tsorter=\"numeric\">".LANG_NOTES_TABLECOLUMNCAPTION_DAYSSINCEMODIFICATION."</th>\n".
             "                  <th tsorter:data-tsorter=\"default\">".LANG_NOTES_TABLECOLUMNCAPTION_MARKINGS."</th>\n".
             "                  <th class=\"noprint\">".LANG_NOTES_TABLECOLUMNCAPTION_ACTION."</th>\n".
             "                </tr>\n".
             "              </thead>\n".
             "              <tbody>\n";

        foreach ($notes as $note)
        {
            if ((int)$note['status'] !== NOTE_STATUS_ACTIVE)
            {
                continue;
            }

            echo "                <tr>\n".
                 "                  <td>".((int)$note['priority'])."</td>\n";

            if (array_key_exists((int)$note['category'], $categoriesCached) === true)
            {
                echo "                  <td>".GetNoteCategoryDisplayNameById($note['category'])."</td>\n";
            }
            else
            {
                echo "                  <td>".((int)$note['category'])."</td>\n";
            }

            echo "                  <td>".$note['datetime_modified']."</td>\n".
                 "                  <td style=\"text-align: right;\">".getTimePassString(strtotime($note['datetime_created']), time())."</td>\n".
                 "                  <td style=\"text-align: right;\">".getTimePassString(strtotime($note['datetime_modified']), time())."</td>\n";

            $flags = (int)$note['flags'];
            $flagsString = "";

            if (($flags & NOTE_FLAGS_NEEDACTION) === NOTE_FLAGS_NEEDACTION)
            {
                if (!empty($flagsString))
                {
                    $flagsString .= " ";
                }

                $flagsString .= "!";
            }

            if (($flags & NOTE_FLAGS_NEEDINFORMATION) === NOTE_FLAGS_NEEDINFORMATION)
            {
                if (!empty($flagsString))
                {
                    $flagsString .= " ";
                }

                $flagsString .= "?";
            }

            if (!empty($flagsString))
            {
                if (($flags & NOTE_FLAGS_URGENT) === NOTE_FLAGS_URGENT)
                {
                    $flagsString = "<span class=\"urgent\">".$flagsString."</span>";
                }
            }

            echo "                  <td>".$flagsString."</td>\n";

            if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN ||
                (int)$_SESSION['user_role'] === USER_ROLE_USER)
            {
                echo "                  <td><a href=\"note_details.php?id=".((int)$note['id'])."\" class=\"noprint\">".LANG_LINKCAPTION_NOTEDETAILS."</a></td>\n";
            }

            echo "                </tr>\n";
        }

        echo "              </tbody>\n".
             "            </table>\n";
    }
}
else
{
    /** @todo Error message. */
}

echo "            <a href=\"index.php\" class=\"noprint\">".LANG_LINKCAPTION_BACK."</a>\n".
     "          </div>\n".
     "        </div>\n".
     "        <div class=\"footerbox\">\n".
     "          <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
     "        </div>\n".
     "    </body>\n".
     "</html>\n";


function getTimePassString($start, $end)
{
    $timeDiff = $end - $start;

    $seconds = $timeDiff % 60;
    $timeDiff -= $seconds;

    if ($timeDiff <= 0)
    {
        return 0;
    }

    $timeDiff /= 60;
    $minutes = $timeDiff % 60;
    $timeDiff -= $minutes;

    if ($timeDiff <= 0)
    {
        return 0;
    }

    $timeDiff /= 60;
    $hours = $timeDiff % 24;
    $timeDiff -= $hours;

    if ($timeDiff <= 0)
    {
        return 0;
    }

    $timeDiff /= 24;

    return $timeDiff;
}



?>
