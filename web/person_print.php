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
 * @file $/web/person_print.php
 * @brief Printable report of person details and notes.
 * @author Stephan Kreutzer
 * @since 2017-05-05
 */



require_once("./libraries/https.inc.php");
require_once("./libraries/session.inc.php");

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
        http_response_code(400);
        exit(-1);
    }
}
else
{
    http_response_code(400);
    exit(-1);
}



require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("person_print"));
require_once("./libraries/person_management.inc.php");


echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
     "<!DOCTYPE html\n".
     "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
     "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
     "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:tsorter=\"http://www.terrill.ca/sorting\" xml:lang=\"".getCurrentLanguage()."\" lang=\"".getCurrentLanguage()."\">\n".
     "    <head>\n".
     "        <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>\n".
     "        <title>".LANG_PAGETITLE."</title>\n".
     "        <link rel=\"profile\" href=\"http://microformats.org/profile/hcard\"/>\n".
     "        <style type=\"text/css\">\n".
     "          .table\n".
     "          {\n".
     "              display: table;\n".
     "          }\n".
     "\n".
     "         .tr\n".
     "         {\n".
     "             display: table-row;\n".
     "         }\n".
     "\n".
     "         .thead\n".
     "         {\n".
     "             display: table-header-group;\n".
     "         }\n".
     "\n".
     "         .tbody\n".
     "         {\n".
     "             display: table-row-group;\n".
     "         }\n".
     "\n".
     "         .tfoot\n".
     "         {\n".
     "             display: table-footer-group;\n".
     "         }\n".
     "\n".
     "         .th\n".
     "         {\n".
     "             display: table-cell;\n".
     "             font-weight: bold;\n".
     "         }\n".
     "\n".
     "         .td\n".
     "         {\n".
     "             display: table-cell;\n".
     "         }\n".
     "\n".
     "          .th, .td\n".
     "          {\n".
     "              padding: 0px 10px 0px 0px;\n".
     "          }\n".
     "        </style>\n".
     "        <style type=\"text/css\" media=\"print\">\n".
     "          .noprint\n".
     "          {\n".
     "              display: none !important;\n".
     "          }\n".
     "        </style>\n".
     "    </head>\n".
     "    <body>\n".
     "        <div>\n".
     "          <div>\n".
     "            <h1>".LANG_HEADER."</h1>\n".
     "          </div>\n".
     "          <div>\n";

$person = GetPersonById($personId);
$notes = null;

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

    require_once("./libraries/note_management.inc.php");

    $notes = GetNotes($personId);
}
else
{
    /** @todo Error message. */
}

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

        echo "            <div>\n".
             "              <h2>".LANG_NOTES_HEADER."</h2>\n";

        foreach ($notes as $note)
        {
            if ((int)$note['status'] != NOTE_STATUS_ACTIVE &&
                (int)$note['status'] != NOTE_STATUS_COMPLETED &&
                (int)$_SESSION['user_role'] != USER_ROLE_ADMIN)
            {
                continue;
            }

            echo "              <div>\n".
                 "                <hr/>\n".
                 "                <div class=\"table\">\n".
                 "                  <div class=\"tr\">\n".
                 "                    <span class=\"th\">".LANG_NOTES_TABLECOLUMNCAPTION_MODIFIED."</span> <span class=\"td\">".$note['datetime_modified']."</span>\n";

            if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN &&
                (int)$note['status'] === NOTE_STATUS_TRASHED)
            {
                echo "                    <span class=\"th\">".LANG_NOTES_TABLECOLUMNCAPTION_CREATED."</span> <span class=\"td\" style=\"text-decoration: line-through;\">".$note['datetime_created']."</span>\n";
            }
            else
            {
                echo "                    <span class=\"th\">".LANG_NOTES_TABLECOLUMNCAPTION_CREATED."</span> <span class=\"td\">".$note['datetime_created']."</span>\n";
            }

            echo "                  </div>\n".
                 "                  <div class=\"tr\">\n".
                 "                    <span class=\"th\">".LANG_NOTES_TABLECOLUMNCAPTION_PRIORITY."</span> <span class=\"td\">".((int)$note['priority'])."</span>\n".
                 "                    <span class=\"th\">".LANG_NOTES_TABLECOLUMNCAPTION_ASSIGNED."</span> <span class=\"td\">".htmlspecialchars($note['user_assigned_name'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</span>\n".
                 "                  </div>\n".
                 "                  <div class=\"tr\">\n";

            if (array_key_exists((int)$note['category'], $categoriesCached) === true)
            {
                echo "                    <span class=\"th\">".LANG_NOTES_TABLECOLUMNCAPTION_CATEGORY."</span> <span class=\"td\">".GetNoteCategoryDisplayNameById($note['category'])."</span>\n";
            }
            else
            {
                echo "                    <span class=\"th\">".LANG_NOTES_TABLECOLUMNCAPTION_CATEGORY."</span> <span class=\"td\">".((int)$note['category'])."</span>\n";
            }

            $flags = (int)$note['flags'];
            $flagsString = "";

            if (($flags & NOTE_FLAGS_URGENT) === NOTE_FLAGS_URGENT &&
                (int)$note['status'] !== NOTE_STATUS_COMPLETED)
            {
                if (!empty($flagsString))
                {
                    $flagsString .= ", ";
                }

                $flagsString .= LANG_FLAGURGENT;
            }

            if ((int)$note['status'] === NOTE_STATUS_COMPLETED)
            {
                if (!empty($flagsString))
                {
                    $flagsString .= ", ";
                }

                $flagsString .= LANG_NOTE_STATUS_COMPLETED;
            }

            if (($flags & NOTE_FLAGS_NEEDACTION) === NOTE_FLAGS_NEEDACTION)
            {
                if (!empty($flagsString))
                {
                    $flagsString .= ", ";
                }

                $flagsString .= LANG_FLAGNEEDACTION;
            }

            if (($flags & NOTE_FLAGS_NEEDINFORMATION) === NOTE_FLAGS_NEEDINFORMATION)
            {
                if (!empty($flagsString))
                {
                    $flagsString .= ", ";
                }

                $flagsString .= LANG_FLAGNEEDINFORMATION;
            }

            if (($flags & NOTE_FLAGS_INFORMATIVE) === NOTE_FLAGS_INFORMATIVE)
            {
                if (!empty($flagsString))
                {
                    $flagsString .= ", ";
                }

                $flagsString .= LANG_FLAGINFORMATIVE;
            }

            echo "                    <span class=\"th\">".LANG_NOTES_TABLECOLUMNCAPTION_MARKINGS."</span> <span class=\"td\">".$flagsString."</span>\n".
                 "                  </div>\n".
                 "                </div>\n".
                 "                <p>\n".
                 "                  ".htmlspecialchars($note['text'], ENT_COMPAT | ENT_HTML401, "UTF-8")."\n".
                 "                </p>\n";

            $uploadsText = "";
            $uploads = GetNoteUploads((int)$note['id']);

            if (is_array($uploads) === true)
            {
                if (count($uploads) > 0)
                {
                    foreach ($uploads as $upload)
                    {
                        if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN &&
                            (int)$upload['status'] !== NOTE_UPLOAD_STATUS_PUBLIC)
                        {
                            continue;
                        }

                        $uploadsText .= "              <li>";

                        if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN &&
                            (int)$upload['status'] === NOTE_UPLOAD_STATUS_TRASHED)
                        {
                            $uploadsText .= "<span style=\"text-decoration: line-through;\">";
                        }

                        $uploadsText .= htmlspecialchars($upload['display_name'], ENT_COMPAT | ENT_HTML401, "UTF-8");
                        $uploadsText .= " (".htmlspecialchars($upload['datetime_created']).")";

                        if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN &&
                            (int)$upload['status'] === NOTE_UPLOAD_STATUS_TRASHED)
                        {
                            $uploadsText .= "</span>";
                        }

                        $uploadsText .= "</li>\n";
                    }
                }
            }

            if (empty($uploadsText) !== true)
            {
                echo "                <p>\n".
                     "                  ".LANG_ATTACHED_FILES."\n".
                     "                </p>\n".
                     "                <ul>\n".
                     $uploadsText.
                     "                </ul>\n";
            }

            echo "              </div>\n";
        }

        echo "            </div>\n";
    }
}

echo "            <a href=\"persons.php\" class=\"noprint\">".LANG_LINKCAPTION_PERSONS."</a>\n".
     "          </div>\n".
     "        </div>\n".
     "        <div class=\"footerbox\">\n".
     "          <a href=\"license.php\" class=\"footerbox_link noprint\">".LANG_LICENSE."</a>\n".
     "        </div>\n".
     "    </body>\n".
     "</html>\n";




?>
