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
 * @file $/web/tasks_unassigned.php
 * @brief Displays persons with notes that need action but aren't already
 *     assigned to a user.
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
require_once(getLanguageFile("tasks_unassigned"));
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
     "              tsorter.create('notesstats_table', 1);\n".
     "          };\n".
     "        </script>\n".
     "    </head>\n".
     "    <body>\n".
     "        <div class=\"mainbox\">\n".
     "          <div class=\"mainbox_header\">\n".
     "            <h1 class=\"mainbox_header_h1\">".LANG_HEADER."</h1>\n".
     "          </div>\n".
     "          <div class=\"mainbox_body\">\n";

$notesStats = GetNotesStats();

if (is_array($notesStats) === true)
{
    if (empty($notesStats) === false)
    {
        $personIds = array();

        foreach ($notesStats as $noteStats)
        {
            $personIds[] = (int)$noteStats['id_person'];
        }

        require_once("./libraries/person_management.inc.php");

        $persons = GetPersonsByIds($personIds);
        $personsById = array();

        if (!empty($persons))
        {
            foreach ($persons as $person)
            {
                $personsById[(int)$person['id']] = $person;
            }
        }

        echo "            <table id=\"notesstats_table\">\n".
             "              <thead>\n".
             "                <tr>\n".
             "                  <th tsorter:data-tsorter=\"numeric\">".LANG_NOTES_TABLECOLUMNCAPTION_PERSON_ID."</th>\n".
             "                  <th tsorter:data-tsorter=\"default\">".LANG_NOTES_TABLECOLUMNCAPTION_PERSON_FAMILYNAME."</th>\n".
             "                  <th tsorter:data-tsorter=\"default\">".LANG_NOTES_TABLECOLUMNCAPTION_PERSON_GIVENNAME."</th>\n".
             "                  <th tsorter:data-tsorter=\"numeric\">".LANG_NOTES_TABLECOLUMNCAPTION_URGENT."</th>\n".
             "                  <th tsorter:data-tsorter=\"numeric\">".LANG_NOTES_TABLECOLUMNCAPTION_NEEDACTION."</th>\n".
             "                  <th tsorter:data-tsorter=\"default\">".LANG_NOTES_TABLECOLUMNCAPTION_NEEDINFORMATION."</th>\n".
             "                  <th class=\"noprint\">".LANG_NOTES_TABLECOLUMNCAPTION_ACTION."</th>\n".
             "                </tr>\n".
             "              </thead>\n".
             "              <tbody>\n";

        foreach ($notesStats as $noteStats)
        {
            echo "                <tr>\n".
                 "                  <td>".((int)$noteStats['id_person'])."</td>\n";

            if (array_key_exists((int)$noteStats['id_person'], $personsById) === true)
            {
                if ((int)$personsById[(int)$noteStats['id_person']]['status'] == PERSON_STATUS_ACTIVE)
                {
                    echo "                  <td>".$personsById[(int)$noteStats['id_person']]['family_name']."</td>\n".
                         "                  <td>".$personsById[(int)$noteStats['id_person']]['given_name']."</td>\n";
                }
                else
                {
                    echo "                  <td></td>\n".
                         "                  <td></td>\n";
                }
            }
            else
            {
                echo "                  <td></td>\n".
                     "                  <td></td>\n";
            }

            if ((int)$noteStats['flag_urgent'] > 0)
            {
                echo "                  <td><span style=\"color:red;\">".((int)$noteStats['flag_urgent'])."</span></td>\n";
            }
            else
            {
                echo "                  <td>".((int)$noteStats['flag_urgent'])."</td>\n";
            }

            echo "                  <td>".((int)$noteStats['flag_needaction'])."</td>\n".
                 "                  <td>".((int)$noteStats['flag_needinformation'])."</td>\n".
                 "                  <td><a href=\"person_details.php?id=".((int)$noteStats['id_person'])."\" class=\"noprint\">".LANG_LINKCAPTION_DETAILS."</a></td>\n".
                 "                </tr>\n";
        }

        echo "              </tbody>\n".
             "            </table>\n";
    }
}
else
{
    /** @todo Error message. */
}

echo "            <a href=\"index.php\" class=\"noprint\">".LANG_LINKCAPTION_DONE."</a>\n".
     "          </div>\n".
     "        </div>\n".
     "        <div class=\"footerbox\">\n".
     "          <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
     "        </div>\n".
     "    </body>\n".
     "</html>\n";

     

?>
