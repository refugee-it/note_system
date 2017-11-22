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
 * @file $/web/person_details.php
 * @brief View details of a person.
 * @author Stephan Kreutzer
 * @since 2014-06-08
 */



require_once("./libraries/https.inc.php");
require_once("./libraries/session.inc.php");

require_once("./libraries/user_defines.inc.php");

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN &&
    (int)$_SESSION['user_role'] !== USER_ROLE_USER)
{
    http_response_code(403);
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

if ($personId == null &&
    isset($_POST['id']) === true)
{
    if (is_numeric($_POST['id']) === true)
    {
        $personId = (int)$_POST['id'];
    }
    else
    {
        http_response_code(400);
        exit(-1);
    }
}

if ($personId == null)
{
    http_response_code(422);
    exit(-1);
}

if (isset($_POST['subscribe']) === true)
{
    require_once("./libraries/subscription_management.inc.php");

    if (in_array($personId, $_SESSION['subscriptions']) !== true)
    {
        if (SetSubscription((int)$_SESSION['user_id'], $personId, true) === 0)
        {
            $_SESSION['subscriptions'][] = $personId;
        }
    }
    else
    {
        if (SetSubscription((int)$_SESSION['user_id'], $personId, false) === 0)
        {
            $index = array_search($personId, $_SESSION['subscriptions']);

            if ($index !== false)
            {
                unset($_SESSION['subscriptions'][$index]);
            }
        }
    }
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
     "        <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>\n".
     "        <title>".LANG_PAGETITLE."</title>\n".
     "        <link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"mainstyle.css\"/>\n".
     "        <link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"mainstyle_print.css\"/>\n".
     "        <link rel=\"profile\" href=\"http://microformats.org/profile/hcard\"/>\n".
     "        <style type=\"text/css\">\n".
     /** @todo Check, if this is still needed, because mainstyle.css should provide those classes by now. */
     "          .th, .td\n".
     "          {\n".
     "              padding: 0px 10px 0px 0px;\n".
     "          }\n".
     "\n".
     "          .urgent\n".
     "          {\n".
     "              color: red;\n".
     "          }\n".
     "\n".
     "          .completed\n".
     "          {\n".
     "              color: green;\n".
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

    $subscribed = false;

    if (isset($_SESSION['subscriptions']) === true)
    {
        if (is_array($_SESSION['subscriptions']) === true)
        {
            $subscribed = in_array($personId, $_SESSION['subscriptions']);
        }
    }

    echo "                <span class=\"th noprint\">".LANG_TABLECOLUMNCAPTION_SUBSCRIPTION."</span>\n".
         "                <span class=\"td noprint\">\n".
         "                  <form action=\"person_details.php\" method=\"post\">\n".
         "                    <input type=\"hidden\" name=\"id\" value=\"".$personId."\"/>\n";

    if ($subscribed === false)
    {
        echo "                    <input type=\"submit\" name=\"subscribe\" value=\"".LANG_BUTTON_SUBSCRIPTIONS_ON."\" class=\"noprint\"/>\n";
    }
    else
    {
        echo "                    <input type=\"submit\" name=\"subscribe\" value=\"".LANG_BUTTON_SUBSCRIPTIONS_OFF."\" class=\"noprint\"/>\n";
    }

    echo "                  </form>\n".
         "                </span>\n".
         "              </div>\n".
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
         "            </div>\n".
         "            <a href=\"note_add.php?person_id=".htmlspecialchars($person['id'], ENT_COMPAT | ENT_HTML401, "UTF-8")."\" class=\"noprint\">".LANG_LINKCAPTION_ADDNOTE."</a>\n";

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

        echo "            <table id=\"note_table\">\n".
             "              <thead>\n".
             "                <tr>\n".
             "                  <th tsorter:data-tsorter=\"numeric\">".LANG_NOTES_TABLECOLUMNCAPTION_PRIORITY."</th>\n".
             "                  <th tsorter:data-tsorter=\"default\">".LANG_NOTES_TABLECOLUMNCAPTION_CATEGORY."</th>\n".
             "                  <th tsorter:data-tsorter=\"date\">".LANG_NOTES_TABLECOLUMNCAPTION_MODIFIED."</th>\n".
             "                  <th tsorter:data-tsorter=\"default\">".LANG_NOTES_TABLECOLUMNCAPTION_MARKINGS."</th>\n".
             "                  <th tsorter:data-tsorter=\"default\">".LANG_NOTES_TABLECOLUMNCAPTION_ASSIGNED."</th>\n";

        if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN ||
            (int)$_SESSION['user_role'] === USER_ROLE_USER)
        {
            echo "                  <th class=\"noprint\">".LANG_NOTES_TABLECOLUMNCAPTION_ACTION."</th>\n";
        }

        echo "                </tr>\n".
             "              </thead>\n".
             "              <tbody>\n";

        foreach ($notes as $note)
        {
            if ((int)$note['status'] != NOTE_STATUS_ACTIVE &&
                (int)$note['status'] != NOTE_STATUS_COMPLETED &&
                (int)$_SESSION['user_role'] != USER_ROLE_ADMIN)
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

            if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN &&
                (int)$note['status'] === NOTE_STATUS_TRASHED)
            {
                echo "                  <td><span style=\"text-decoration: line-through;\">".$note['datetime_modified']."</span></td>\n";
            }
            else if ((int)$note['status'] === NOTE_STATUS_COMPLETED)
            {
                echo "                  <td><span class=\"completed\">".$note['datetime_modified']."</span></td>\n";
            }
            else
            {
                echo "                  <td>".$note['datetime_modified']."</td>\n";
            }

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

            echo "                  <td>".$flagsString."</td>\n".
                 "                  <td>".htmlspecialchars($note['user_assigned_name'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n";

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

echo "            <a href=\"persons.php\" class=\"noprint\">".LANG_LINKCAPTION_PERSONS."</a>\n".
     "          </div>\n".
     "        </div>\n".
     "        <div class=\"footerbox\">\n".
     "          <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
     "        </div>\n".
     "    </body>\n".
     "</html>\n";




?>
