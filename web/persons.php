<?php
/* Copyright (C) 2014-2016  Stephan Kreutzer
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



session_start();

if (isset($_SESSION['user_id']) !== true)
{
    exit(-1);
}



require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("persons"));
require_once("./libraries/person_management.inc.php");
require_once("./libraries/user_defines.inc.php");

$displayNonpublicData = false;

if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN ||
    (int)$_SESSION['user_role'] === USER_ROLE_USER)
{
    $displayNonpublicData = true;
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
     "<!DOCTYPE html\n".
     "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
     "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
     "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:tsorter=\"http://www.terrill.ca/sorting\" xml:lang=\"".getCurrentLanguage()."\" lang=\"".getCurrentLanguage()."\">\n".
     "    <head>\n".
     "        <title>".LANG_PAGETITLE."</title>\n".
     "        <link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"mainstyle.css\"/>\n".
     "        <link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"mainstyle_print.css\"/>\n".
     "        <meta http-equiv=\"expires\" content=\"1296000\"/>\n".
     "        <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>\n".
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

echo "                </tr>\n".
     "              </thead>\n".
     "              <tbody>\n";

$persons = GetPersons();

if (is_array($persons) === true)
{
    foreach ($persons as $person)
    {
        echo "                <tr>\n".
             "                  <td>".htmlspecialchars($person['id'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n".
             "                  <td>".htmlspecialchars($person['family_name'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n".
             "                  <td>".htmlspecialchars($person['given_name'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n";

        if ($displayNonpublicData === true)
        {
            echo "                  <td>".htmlspecialchars($person['date_of_birth'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n";
        }

        echo "                  <td>".htmlspecialchars($person['location'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n";

        if ($displayNonpublicData === true)
        {
            echo "                  <td>".htmlspecialchars($person['nationality'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n";
        }

        echo "                </tr>\n";
    }
}

echo "              </tbody>\n".
     "            </table>\n".
     "            <form action=\"index.php\" method=\"post\" class=\"noprint\">\n".
     "              <fieldset class=\"noprint\">\n".
     "                <input type=\"submit\" value=\"".LANG_BUTTON_MAINPAGE."\"  class=\"noprint\"/><br/>\n".
     "              </fieldset>\n".
     "            </form>\n".
     "          </div>\n".
     "        </div>\n".
     "        <div class=\"footerbox\">\n".
     "          <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
     "        </div>\n".
     "    </body>\n".
     "</html>\n";




?>
