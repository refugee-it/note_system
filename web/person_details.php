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
 * @file $/web/person_details.php
 * @brief View details of a person.
 * @author Stephan Kreutzer
 * @since 2014-06-08
 */



require_once("./libraries/https.inc.php");

session_start();

if (isset($_SESSION['user_id']) !== true)
{
    exit(-1);
}

require_once("./libraries/user_defines.inc.php");

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN &&
    (int)$_SESSION['user_role'] !== USER_ROLE_USER)
{
    exit(-1);
}

$personId = null;

if (isset($_GET['id']) === true)
{
    if (is_numeric($_GET['id']) === true)
    {
        $personId = (int)$_GET['id'];
    }
    else
    {
        exit(-1);
    }
}
else
{
    exit(-1);
}



require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("person_details"));
require_once("./libraries/person_management.inc.php");


echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
     "<!DOCTYPE html\n".
     "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
     "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
     "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:tsorter=\"http://www.terrill.ca/sorting\" xml:lang=\"".getCurrentLanguage()."\" lang=\"".getCurrentLanguage()."\">\n".
     "    <head>\n".
     "        <title>".LANG_PAGETITLE."</title>\n".
     "        <link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"mainstyle.css\"/>\n".
     "        <link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"mainstyle_print.css\"/>\n".
     "        <link rel=\"profile\" href=\"http://microformats.org/profile/hcard\"/>\n".
     "        <meta http-equiv=\"expires\" content=\"1296000\"/>\n".
     "        <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>\n".
     "        <style type=\"text/css\">\n".
     "          .th, .td\n".
     "          {\n".
     "              padding: 0px 10px 0px 0px;\n".
     "          }\n".
     "        </style>\n".
     "    </head>\n".
     "    <body>\n".
     "        <div class=\"mainbox\">\n".
     "          <div class=\"mainbox_header\">\n".
     "            <h1 class=\"mainbox_header_h1\">".LANG_HEADER."</h1>\n".
     "          </div>\n".
     "          <div class=\"mainbox_body\">\n";

$person = GetPersonById($personId);

if (is_array($person) === true)
{
    echo "            <div class=\"vcard table\">\n".
         "              <div class=\"tr\">\n".
         "                <span class=\"th\">".LANG_TABLECOLUMNCAPTION_ID."</span> <span class=\"td\">".htmlspecialchars($person['id'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</span>\n";

    if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN)
    {
        echo "                <span class=\"th\">".LANG_TABLECOLUMNCAPTION_DATEOFBIRTH."</span> <span class=\"td bday\">".htmlspecialchars($person['date_of_birth'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</span>\n";
    }

    echo "              </div>\n".
         "              <div class=\"tr\">\n".
         "                <span class=\"th\">".LANG_TABLECOLUMNCAPTION_FAMILYNAME."</span> <span class=\"family-name td\">".htmlspecialchars($person['family_name'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</span>\n".
         "                <span class=\"th\">".LANG_TABLECOLUMNCAPTION_PLACEOFLIVING."</span> <span class=\"td\">".htmlspecialchars($person['location'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</span>\n".
         "              </div>\n".
         "              <div class=\"tr\">\n".
         "                <span class=\"th\">".LANG_TABLECOLUMNCAPTION_GIVENNAME."</span> <span class=\"given-name td\">".htmlspecialchars($person['given_name'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</span>\n";

    if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN)
    {
        require_once("./custom/nationality.inc.php");

        echo "                <span class=\"th\">".LANG_TABLECOLUMNCAPTION_NATIONALITY."</span> <span class=\"td bday\">".GetNationalityDisplayNameById($person['nationality'])."</span>\n";
    }

    echo "              </div>\n".
         "            </div>\n";
}

echo "            <a href=\"note_add.php?person_id=".htmlspecialchars($person['id'], ENT_COMPAT | ENT_HTML401, "UTF-8")."\" class=\"noprint\">".LANG_LINKCAPTION_ADDNOTE."</a>\n".
     "            <a href=\"persons.php\" class=\"noprint\">".LANG_LINKCAPTION_PERSONS."</a>\n";

require_once("./libraries/note_management.inc.php");

$notes = GetNotes($personId);

if (is_array($notes) === true)
{
    if (count($notes) > 0)
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

        echo "              <div>\n";

        foreach ($notes as $note)
        {
            if ((int)$note['status'] != NOTE_STATUS_ACTIVE &&
                (int)$_SESSION['user_role'] != USER_ROLE_ADMIN)
            {
                continue;
            }

            echo "                <hr/>\n".
                 "                <div class=\"table\">\n".
                 "                  <div class=\"tr\">\n".
                 "                    <span class=\"td\">".LANG_CAPTION_NOTEPRIORITY."</span> <span class=\"td\">".$note['priority']."</span>\n";

            if (array_key_exists((int)$note['category'], $categoriesCached) === true)
            {
                echo "                    <span class=\"td\">".LANG_CAPTION_NOTECATEGORY."</span> <span class=\"td\">".GetNoteCategoryDisplayNameById($note['category'])."</span>\n";
            }
            else
            {
                echo "                    <span class=\"td\">".LANG_CAPTION_NOTECATEGORY."</span> <span class=\"td\">".$note['category']."</span>\n";
            }

            echo "                  </div>\n".
                 "                  <div class=\"tr\">\n".
                 "                    <span class=\"td\">".LANG_CAPTION_NOTEOWNER."</span> <span class=\"td\">".$note['user_name']."</span>\n".
                 "                    <span class=\"td\">".LANG_CAPTION_NOTEMODIFIED."</span> <span class=\"td\">".$note['datetime_modified']."</span>\n".
                 "                  </div>\n".
                 "                </div>\n".
                 "                <p>\n".
                 "                  ".$note['text']."\n".
                 "                </p>\n";
        }
    }

    echo "              </div>\n";
}

echo "          </div>\n".
     "        </div>\n".
     "        <div class=\"footerbox\">\n".
     "          <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
     "        </div>\n".
     "    </body>\n".
     "</html>\n";




?>
