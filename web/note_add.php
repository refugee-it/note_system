<?php
/* Copyright (C) 2012-2016  Stephan Kreutzer
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
 * @file $/web/note_add.php
 * @brief Add a note to a person.
 * @author Stephan Kreutzer
 * @since 2016-12-03
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

require_once("./libraries/user_defines.inc.php");

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN &&
    (int)$_SESSION['user_role'] !== USER_ROLE_USER)
{
    exit(-1);
}

$personId = null;

if (isset($_GET['person_id']) === true)
{
    if (is_numeric($_GET['person_id']) === true)
    {
        $personId = (int)$_GET['person_id'];
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

require_once(dirname(__FILE__)."/libraries/person_management.inc.php");

$person = GetPersonById($personId);

if (is_array($person) !== true)
{
    header("HTTP/1.1 404 Not Found");
    exit(1);
}



require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("note_add"));

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
     "<!DOCTYPE html\n".
     "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
     "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
     "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"".getCurrentLanguage()."\" lang=\"".getCurrentLanguage()."\">\n".
     "  <head>\n".
     "    <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>\n".
     "    <title>".LANG_PAGETITLE."</title>\n".
     "    <link rel=\"stylesheet\" type=\"text/css\" href=\"mainstyle.css\"/>\n".
     "  </head>\n".
     "  <body>\n".
     "    <div class=\"mainbox\">\n".
     "      <div class=\"mainbox_header\">\n".
     "        <h1 class=\"mainbox_header_h1\">".LANG_HEADER."</h1>\n".
     "      </div>\n".
     "      <div class=\"mainbox_body\">\n";

require_once("./libraries/note_category.inc.php");

$categories = GetNoteCategoryDefinitions();

$categoryId = null;

$priority = null;

if (isset($_POST['priority']) === true)
{
    $priority = $_POST['priority'];

    if (empty($priority) != true)
    {
        $priority = (int)$priority;
    }
    else
    {
        $priority = null;
    }
}

if (isset($_POST['category']) === true)
{
    $categoryId = $_POST['category'];

    if (empty($categoryId) != true)
    {
        $categoryId = (int)$categoryId;
        $found = false;

        foreach ($categories as $category)
        {
            if ($category->getId() === $categoryId)
            {
                $found = true;

                if ($priority === null)
                {
                    $priority = (int)$category->getDefaultPriority();
                }

                break;
            }
        }

        if ($found !== true)
        {
            $categoryId = null;
        }
    }
    else
    {
        $categoryId = null;
    }
}

$text = null;

if (isset($_POST['text']) === true)
{
    $text = $_POST['text'];

    if (empty($text) == true)
    {
        $text = null;
    }
}

$createSuccess = null;

if ($personId != null &&
    $categoryId != null &&
    $text != null)
{
    require_once("./libraries/note_management.inc.php");

    $id = AttachNewNode($personId, $categoryId, $priority, $text, (int)$_SESSION['user_id']);

    if ($id > 0)
    {
        $createSuccess = true;
    }
    else
    {
        $createSuccess = false;
    }
}

if ($createSuccess !== null)
{
    if ($createSuccess === true)
    {
        echo "        <p>\n".
             "          <span class=\"success\">".LANG_OPERATIONSUCCEEDED."</span>\n".
             "        </p>\n".
             "        <a href=\"note_add.php?person_id=".htmlspecialchars($personId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\">".LANG_AGAIN."</a>\n".
             "        <a href=\"person_details.php?id=".htmlspecialchars($personId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\">".LANG_BACK."</a>\n";
    }
    else
    {
        echo "        <p>\n".
             "          <span class=\"error\">".LANG_OPERATIONFAILED."</span>\n".
             "        </p>\n";
    }
}

if ($createSuccess !== true)
{
    echo "        <div>\n".
         "          <form action=\"note_add.php?person_id=".htmlspecialchars($personId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\" method=\"post\">\n".
         "            <fieldset>\n".
         "              <select name=\"category\" size=\"1\">\n";

    foreach ($categories as $category)
    {
        if ($category->getName() == "unknown" ||
            $category->getName() == "none")
        {
            continue;
        }

        echo "                <option value=\"".$category->getId()."\"";

        if ($category->getId() == $categoryId)
        {
            echo " selected=\"selected\"";
        }

        echo ">".GetNoteCategoryDisplayNameById($category->getId())."</option>\n";
    }

    echo "              </select> ".LANG_CATEGORY."<br/>\n".
         "              <input type=\"text\" name=\"priority\" value=\"".htmlspecialchars($priority, ENT_COMPAT | ENT_HTML401, "UTF-8")."\" size=\"10\" maxlength=\"10\"/> ".LANG_PRIORITY."<br/>\n".
         "              <textarea name=\"text\" rows=\"24\" cols=\"80\">".htmlspecialchars($text, ENT_COMPAT | ENT_HTML401, "UTF-8")."</textarea><br/>\n".
         "              <input type=\"submit\" name=\"save\" value=\"".LANG_SUBMITBUTTON."\"/>\n".
         "            </fieldset>\n".
         "          </form>\n".
         "        </div>\n".
         "        <a href=\"person_details.php?id=".htmlspecialchars($personId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\">".LANG_BACK."</a>\n";;
}

echo "      </div>\n".
     "    </div>\n".
     "    <div class=\"footerbox\">\n".
     "      <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
     "    </div>\n".
     "  </body>\n".
     "</html>\n".
     "\n";


?>
