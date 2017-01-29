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
 * @file $/web/index.php
 * @brief Start page.
 * @author Stephan Kreutzer
 * @since 2012-06-01
 */



require_once("./libraries/https.inc.php");

if (empty($_SESSION) === true)
{
    @session_start();
}

if (isset($_POST['logout']) === true &&
    isset($_SESSION['user_id']) === true)
{
    require_once("./libraries/logging.inc.php");

    if (isset($_SESSION['user_name']) === true)
    {
        logEvent("User '".$_SESSION['user_name']."' logged out.");
    }
    else
    {
        logEvent("User ".((int)$_SESSION['user_id'])." logged out.");
    }

    $language = null;

    if (isset($_SESSION['language']) === true)
    {
        $language = $_SESSION['language'];
    }

    $_SESSION = array();

    if ($language != null)
    {
        $_SESSION['language'] = $language;
    }
    else
    {
        if (isset($_COOKIE[session_name()]) == true)
        {
            setcookie(session_name(), '', time()-42000, '/');
        }
    }
}



require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("index"));
require_once("./language_selector.inc.php");

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
     "<!DOCTYPE html\n".
     "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
     "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
     "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"".getCurrentLanguage()."\" lang=\"".getCurrentLanguage()."\">\n".
     "    <head>\n".
     "        <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>\n".
     "        <title>".LANG_PAGETITLE."</title>\n".
     "        <link rel=\"stylesheet\" type=\"text/css\" href=\"mainstyle.css\"/>\n".
     "    </head>\n".
     "    <body>\n";

if (isset($_POST['name']) !== true ||
    isset($_POST['passwort']) !== true)
{
    require_once("./language_selector.inc.php");
    echo getHTMLLanguageSelector("index.php");

    echo "        <div class=\"mainbox\">\n".
         "          <div class=\"mainbox_header\">\n".
         "            <h1 class=\"mainbox_header_h1\">".LANG_HEADER."</h1>\n".
         "          </div>\n".
         "          <div class=\"mainbox_body\">\n";

    if (isset($_POST['install_done']) == true)
    {
        if (@unlink(dirname(__FILE__)."/install/install.php") === true)
        {
            clearstatcache();
        }
        else
        {
            echo "            <p class=\"error\">\n".
                 "              ".LANG_INSTALLDELETEFAILED."\n".
                 "            </p>\n";
        }
    }

    if (file_exists("./install/install.php") === true &&
        isset($_GET['skipinstall']) != true)
    {
        echo "            <form action=\"install/install.php\" method=\"post\" class=\"installbutton_form\">\n".
             "              <fieldset>\n".
             "                <input type=\"submit\" value=\"".LANG_INSTALLBUTTON."\"/><br/>\n".
             "              </fieldset>\n".
             "            </form>\n";

        require_once("./license.inc.php");
        echo getHTMLLicenseNotification("license");
    }
    else
    {
        require_once("./libraries/user_management.inc.php");

        if (isset($_SESSION['user_id']) === true)
        {
            echo "            <a href=\"tasks_user.php\">".LANG_LINKCAPTION_MYTASKS."</a><br/>\n".
                 "            <a href=\"tasks_unassigned.php\">".LANG_LINKCAPTION_UNASSIGNEDTASKS."</a><br/>\n".
                 "            <a href=\"persons.php\">".LANG_LINKCAPTION_PERSONS."</a><br/>\n";

            if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN)
            {
                echo "            <a href=\"admin_user_create.php\">".LANG_LINKCAPTION_ADMINUSERCREATE."</a><br/>\n".
                     "            <a href=\"admin_logs_view.php\">".LANG_LINKCAPTION_ADMINLOGSVIEW."</a><br/>\n";
            }

            echo "            <form action=\"index.php\" method=\"post\">\n".
                 "              <fieldset>\n".
                 "                <input type=\"submit\" name=\"logout\" value=\"".LANG_BUTTON_LOGOUT."\"/><br/>\n".
                 "              </fieldset>\n".
                 "            </form>\n";
        }
        else
        {
            echo "            <p>\n".
                 "              ".LANG_WELCOMETEXT."\n".
                 "            </p>\n".
                 "            <p>\n".
                 "              ".LANG_LOGINDESCRIPTION."\n".
                 "            </p>\n".
                 "            <form action=\"index.php\" method=\"post\">\n".
                 "              <fieldset>\n".
                 "                <input name=\"name\" type=\"text\" size=\"20\" maxlength=\"60\"/> ".LANG_NAMEFIELD_CAPTION."<br />\n".
                 "                <input name=\"passwort\" type=\"password\" size=\"20\" maxlength=\"60\"/> ".LANG_PASSWORDFIELD_CAPTION."<br />\n".
                 "                <input type=\"submit\" value=\"".LANG_SUBMITBUTTON."\"/><br/>\n".
                 "              </fieldset>\n".
                 "            </form>\n";

            require_once("./license.inc.php");
            echo getHTMLLicenseNotification("license");
        }
    }

    echo "          </div>\n".
         "        </div>\n".
         "        <div class=\"footerbox\">\n".
         "          <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
         "        </div>\n".
         "    </body>\n".
         "</html>\n".
         "\n";
}
else
{
    require_once("./libraries/user_management.inc.php");

    $user = NULL;

    $result = getUserByName($_POST['name']);

    if (is_array($result) !== true)
    {
        echo "        <div class=\"mainbox\">\n".
             "          <div class=\"mainbox_body\">\n".
             "            <p class=\"error\">\n".
             "              ".LANG_DBCONNECTFAILED."\n".
             "            </p>\n".
             "          </div>\n".
             "        </div>\n".
             "        <div class=\"footerbox\">\n".
             "          <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
             "        </div>\n".
             "    </body>\n".
             "</html>\n";

        exit(-1);
    }


    if (count($result) === 0)
    {
        echo "        <div class=\"mainbox\">\n".
             "          <div class=\"mainbox_body\">\n".
             "            <p class=\"error\">\n".
             "              ".LANG_LOGINFAILED."\n".
             "            </p>\n".
             "            <a href=\"index.php\">".LANG_LINKCAPTION_RETRYLOGIN."</a>\n".
             "          </div>\n".
             "        </div>\n".
             "        <div class=\"footerbox\">\n".
             "          <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
             "        </div>\n".
             "    </body>\n".
             "</html>\n";

        exit(0);
    }
    else
    {
        // The user does exist, he wants to login.

        if ($result[0]['password'] === hash('sha512', $result[0]['salt'].$_POST['passwort']))
        {
            $user = array("id" => (int)$result[0]['id'],
                          "role" => (int)$result[0]['role']);
        }
        else
        {
            echo "        <div class=\"mainbox\">\n".
                 "          <div class=\"mainbox_body\">\n".
                 "            <p class=\"error\">\n".
                 "              ".LANG_LOGINFAILED."\n".
                 "            </p>\n".
                 "            <a href=\"index.php\">".LANG_LINKCAPTION_RETRYLOGIN."</a>\n".
                 "          </div>\n".
                 "        </div>\n".
                 "        <div class=\"footerbox\">\n".
                 "          <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
                 "        </div>\n".
                 "    </body>\n".
                 "</html>\n";

            exit(0);
        }
    }

    if (is_array($user) === true)
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $_POST['name'];
        $_SESSION['user_role'] = $user['role'];

        require_once("./libraries/logging.inc.php");
        logEvent("User '".$_POST['name']."' logged in.");

        echo "        <div class=\"mainbox\">\n".
             "          <div class=\"mainbox_body\">\n".
             "            <p class=\"success\">\n".
             "              ".LANG_LOGINSUCCESS."\n".
             "            </p>\n".
             "            <a href=\"index.php\">".LANG_LINKCAPTION_CONTINUE."</a>\n".
             "          </div>\n".
             "        </div>\n".
             "        <div class=\"footerbox\">\n".
             "          <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
             "        </div>\n";
    }

    echo "    </body>\n".
         "</html>\n";
}


?>
