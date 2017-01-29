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
 * @file $/web/persons.php
 * @brief Lists all persons.
 * @author Stephan Kreutzer
 * @since 2014-06-08
 */



require_once("./libraries/https.inc.php");

session_start();

if (isset($_SESSION['user_id']) !== true)
{
    exit(-1);
}

if (isset($_SESSION['user_role']) !== true)
{
    exit(-1);
}



require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("persons"));
require_once("./libraries/person_management.inc.php");
require_once("./libraries/note_management.inc.php");
require_once("./libraries/user_defines.inc.php");

$displayNonpublicData = false;

if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN)
{
    $displayNonpublicData = true;
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
     "<!DOCTYPE html\n".
     "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
     "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
     "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:tsorter=\"http://www.terrill.ca/sorting\" xml:lang=\"".getCurrentLanguage()."\" lang=\"".getCurrentLanguage()."\">\n".
     "    <head>\n".
     "        <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>\n".
     "        <title>".LANG_PAGETITLE."</title>\n".
     "        <link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"mainstyle.css\"/>\n".
     "        <link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"mainstyle_print.css\"/>\n".
     "        <link rel=\"profile\" href=\"http://microformats.org/profile/hcard\"/>\n".
     "        <style type=\"text/css\">\n".
     "          .urgent\n".
     "          {\n".
     "              color: red;\n".
     "          }\n".
     "        </style>\n".
     "        <script type=\"text/javascript\" src=\"tsorter.js\"></script>\n".
     "        <script type=\"text/javascript\">\n".
     "          window.onload = function() {\n".
     "            tsorter.create('person_table', 0);\n".
     "          };\n".
     "        </script>\n".
     "    </head>\n".
     "    <body>\n".
     "        <div class=\"mainbox\">\n".
     "          <div class=\"mainbox_header\">\n".
     "            <h1 class=\"mainbox_header_h1\">".LANG_HEADER."</h1>\n".
     "          </div>\n".
     "          <div class=\"mainbox_body\">\n".
     "            <table id=\"person_table\">\n".
     "              <thead>\n".
     "                <tr>\n".
     "                  <th tsorter:data-tsorter=\"numeric\">".LANG_TABLECOLUMNCAPTION_ID."</th>\n".
     "                  <th tsorter:data-tsorter=\"default\">".LANG_TABLECOLUMNCAPTION_FAMILYNAME."</th>\n".
     "                  <th tsorter:data-tsorter=\"default\">".LANG_TABLECOLUMNCAPTION_GIVENNAME."</th>\n";

if ($displayNonpublicData === true)
{
    echo "                  <th tsorter:data-tsorter=\"date\">".LANG_TABLECOLUMNCAPTION_DATEOFBIRTH."</th>\n";
}

echo "                  <th tsorter:data-tsorter=\"default\">".LANG_TABLECOLUMNCAPTION_PLACEOFLIVING."</th>\n";

if ($displayNonpublicData === true)
{
    echo "                  <th tsorter:data-tsorter=\"default\">".LANG_TABLECOLUMNCAPTION_NATIONALITY."</th>\n";
}

if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN)
{
    echo "                  <th tsorter:data-tsorter=\"default\">".LANG_TABLECOLUMNCAPTION_STATUS."</th>\n";
}

if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN ||
    (int)$_SESSION['user_role'] === USER_ROLE_USER)
{
    echo "                  <th class=\"noprint\">".LANG_TABLECOLUMNCAPTION_MARKINGS."</th>\n".
         "                  <th class=\"noprint\">".LANG_TABLECOLUMNCAPTION_ACTION."</th>\n";
}

echo "                </tr>\n".
     "              </thead>\n".
     "              <tbody>\n";

$persons = GetPersons();
$notesStats = GetNotesStats();

if (is_array($persons) === true)
{
    $nationalities = null;

    if ($displayNonpublicData === true)
    {
        require_once("./custom/nationality.inc.php");
        $nationalities = GetNationalityDefinitions();
    }

    foreach ($persons as $person)
    {
        if ((int)$_SESSION['user_role'] != USER_ROLE_ADMIN &&
            (int)$person['status'] != PERSON_STATUS_ACTIVE)
        {
            continue;
        }

        echo "                <tr class=\"vcard\">\n".
             "                  <td>".htmlspecialchars($person['id'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n".
             "                  <td class=\"family-name\">".htmlspecialchars($person['family_name'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n".
             "                  <td class=\"given-name\">".htmlspecialchars($person['given_name'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n";

        if ($displayNonpublicData === true)
        {
            echo "                  <td class=\"bday\">".htmlspecialchars($person['date_of_birth'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n";
        }

        echo "                  <td>".htmlspecialchars($person['location'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n";

        if ($displayNonpublicData === true)
        {
            $nationality = (int)$person['nationality'];

            if (is_array($nationalities) === true)
            {
                if (empty($nationalities) != true)
                {
                    if ($nationality >= 0 &&
                        $nationality < count($nationalities))
                    {
                        $nationality = GetNationalityDisplayName($nationalities[$nationality]);
                    }
                }
            }

            echo "                  <td>".$nationality."</td>\n";
        }

        if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN)
        {
            echo "                  <td>".$person['status']."</td>\n";
        }

        if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN ||
            (int)$_SESSION['user_role'] === USER_ROLE_USER)
        {
            if (is_array($notesStats) === true)
            {
                if (array_key_exists((int)$person['id'], $notesStats) === true)
                {
                    $personNotesStats = $notesStats[$person['id']];
                    $flagsString = "";

                    if ((int)$personNotesStats['flag_needaction'] > 0)
                    {
                        if (!empty($flagsString))
                        {
                            $flagsString .= " ";
                        }

                        $flagsString .= "!";
                    }

                    if ((int)$personNotesStats['flag_needinformation'] > 0)
                    {
                        if (!empty($flagsString))
                        {
                            $flagsString .= " ";
                        }

                        $flagsString .= "?";
                    }

                    if (!empty($flagsString))
                    {
                        if ((int)$personNotesStats['flag_urgent'] > 0)
                        {
                            $flagsString = "<span class=\"urgent\">".$flagsString."</span>";
                        }
                    }

                    echo "                  <td class=\"noprint\">".$flagsString."</td>\n";
                }
                else
                {
                    echo "                  <td class=\"noprint\"></td>\n";
                }
            }
            else if ((int)$notesStats === 1)
            {
                echo "                  <td class=\"noprint\"></td>\n";
            }
            else
            {
                echo "                  <td class=\"noprint\">X</td>\n";
            }

            echo "                  <td class=\"noprint\"><a href=\"person_details.php?id=".((int)$person['id'])."\" class=\"noprint\">".LANG_LINKCAPTION_PERSONDETAILS."</a></td>\n";
        }

        echo "                </tr>\n";
    }
}

echo "              </tbody>\n".
     "            </table>\n".
     "            <a href=\"person_add.php\" class=\"noprint\">".LANG_LINKCAPTION_ADDPERSON."</a>\n".
     "            <a href=\"index.php\" class=\"noprint\">".LANG_LINKCAPTION_MAINPAGE."</a>\n".
     "          </div>\n".
     "        </div>\n".
     "        <div class=\"footerbox\">\n".
     "          <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
     "        </div>\n".
     "    </body>\n".
     "</html>\n";




?>
