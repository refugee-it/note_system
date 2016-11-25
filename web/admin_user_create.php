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
 * @file $/web/admin_user_create.php
 * @brief For creating a new user.
 * @author Stephan Kreutzer
 * @since 2012-06-01
 */



require_once("./libraries/https.inc.php");

session_start();

if (isset($_SESSION['user_id']) !== true)
{
    exit(-1);
}

require_once(dirname(__FILE__)."/libraries/user_management.inc.php");

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN)
{
    exit(-1);
}

require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("admin_user_create"));

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

$userName = null;
$userPassword = null;
$userEMail = null;

if (isset($_POST['username']) === true)
{
    $userName = $_POST['username'];

    if (empty($userName) == true)
    {
        $userName = null;
    }
}

if (isset($_POST['password']) === true)
{
    $userPassword = $_POST['password'];

    if (empty($userPassword) == true)
    {
        $userPassword = null;
    }
}

if (isset($_POST['email']) === true)
{
    $userEMail = $_POST['email'];

    if (empty($userEMail) == true)
    {
        $userEMail = null;
    }
}

if ($userName == null ||
    $userPassword == null ||
    $userEMail == null)
{
    echo "        <div>\n".
         "          <form action=\"admin_user_create.php\" method=\"post\">\n".
         "            <fieldset>\n".
         "              <input type=\"text\" name=\"username\" size=\"20\" maxlength=\"60\"//> ".LANG_NAMEFIELD_CAPTION."<br/>\n".
         "              <input type=\"password\" name=\"password\" size=\"20\" maxlength=\"60\"/> ".LANG_PASSWORDFIELD_CAPTION."<br/>\n".
         "              <input type=\"text\" name=\"email\" size=\"20\" maxlength=\"255\"/> ".LANG_EMAILFIELD_CAPTION."<br/>\n".
         "              <input type=\"submit\" name=\"save\" value=\"".LANG_SUBMITBUTTON."\"/>\n";
         "            </fieldset>\n".
         "          </form>\n".
         "        </div>\n";
}
else
{
    $successCreate = false;

    $id = insertNewUser($userName, $userPassword, $userEMail, USER_ROLE_USER);

    if ($id > 0)
    {
        $user = array("id" => $id);
        $successCreate = true;
    }

    if ($successCreate == true)
    {
        echo "        <p>\n".
             "          <span class=\"success\">".LANG_OPERATIONSUCCEEDED."</span>\n".
             "        </p>\n".
             "        <div>\n".
             "          <a href=\"index.php\">".LANG_BACK."</a>\n".
             "        </div>\n";
    }
    else
    {
        echo "        <p>\n".
             "          <span class=\"error\">".LANG_OPERATIONFAILED."</span>\n".
             "        </p>\n".
             "        <div>\n".
             "          <a href=\"./admin_user_create.php\">".LANG_RETRY."</a>\n".
             "          <a href=\"./index.php\">".LANG_BACK."</a>\n".
             "        </div>\n";
    }
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
