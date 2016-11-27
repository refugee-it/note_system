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
 * @file $/web/person_add.php
 * @brief Add a person.
 * @author Stephan Kreutzer
 * @since 2016-11-23
 */



require_once("./libraries/https.inc.php");

session_start();

if (isset($_SESSION['user_id']) !== true)
{
    exit(-1);
}

require_once(dirname(__FILE__)."/libraries/user_management.inc.php");

require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("person_add"));

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

$familyName = null;
$givenName = null;
$dateOfBirth = null;
$location = null;
$nationality = null;

if (isset($_POST['family_name']) === true)
{
    $familyName = $_POST['family_name'];

    if (empty($familyName) == true)
    {
        $familyName = null;
    }
}

if (isset($_POST['given_name']) === true)
{
    $givenName = $_POST['given_name'];

    if (empty($givenName) == true)
    {
        $givenName = null;
    }
}

if (isset($_POST['date_of_birth']) === true)
{
    $dateOfBirth = $_POST['date_of_birth'];

    if (empty($dateOfBirth) == true)
    {
        $dateOfBirth = null;
    }
}

if (isset($_POST['location']) === true)
{
    $location = $_POST['location'];

    if (empty($location) == true)
    {
        $location = null;
    }
}

if (isset($_POST['nationality']) === true)
{
    $nationality = $_POST['nationality'];

    if (empty($nationality) == true)
    {
        $nationality = null;
    }
}

$displayNonpublicData = false;

if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN ||
    (int)$_SESSION['user_role'] === USER_ROLE_USER)
{
    $displayNonpublicData = true;
}

$createSuccess = null;

if ($familyName !== null ||
    $givenName !== null ||
    $location !== null)
{
    require_once("./libraries/person_management.inc.php");

    $id = InsertNewPerson($familyName, $givenName, $dateOfBirth, $location, (int)$nationality);

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
             "        <div>\n".
             "          <form action=\"person_add.php\" method=\"post\">\n".
             "            <fieldset>\n".
             "              <input type=\"submit\" name=\"again\" value=\"".LANG_AGAIN."\"/>\n".
             "            </fieldset>\n".
             "          </form>\n".
             "        </div>\n".
             "        <div>\n".
             "          <form action=\"persons.php\" method=\"post\">\n".
             "            <fieldset>\n".
             "              <input type=\"submit\" name=\"back\" value=\"".LANG_BACK."\"/>\n".
             "            </fieldset>\n".
             "          </form>\n".
             "        </div>\n";
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
         "          <form action=\"person_add.php\" method=\"post\">\n".
         "            <fieldset>\n".
         "              <input type=\"text\" name=\"family_name\" value=\"".htmlspecialchars($familyName, ENT_COMPAT | ENT_HTML401, "UTF-8")."\" size=\"20\" maxlength=\"254\"/> ".LANG_FAMILYNAMEFIELD_CAPTION."<br/>\n".
         "              <input type=\"text\" name=\"given_name\" value=\"".htmlspecialchars($givenName, ENT_COMPAT | ENT_HTML401, "UTF-8")."\" size=\"20\" maxlength=\"254\"/> ".LANG_GIVENNAMEFIELD_CAPTION."<br/>\n";

    if ($displayNonpublicData === true)
    {
        echo "              <input type=\"text\" name=\"date_of_birth\" value=\"".htmlspecialchars($dateOfBirth, ENT_COMPAT | ENT_HTML401, "UTF-8")."\" size=\"20\" maxlength=\"254\"/> ".LANG_DATEOFBIRTHFIELD_CAPTION."<br/>\n";
    }

    echo "              <input type=\"text\" name=\"location\" value=\"".htmlspecialchars($location, ENT_COMPAT | ENT_HTML401, "UTF-8")."\" size=\"20\" maxlength=\"254\"/> ".LANG_LOCATIONFIELD_CAPTION."<br/>\n";

    if ($displayNonpublicData === true)
    {
        echo "              <select name=\"nationality\" size=\"1\">\n";

        require_once("./custom/nationality.inc.php");

        $nationalities = GetNationalityDefinitions();

        foreach ($nationalities as $id => $value)
        {
            echo "                <option value=\"".$id."\"";

            if ((int)$nationality === (int)$id)
            {
                echo " selected=\"selected\"";
            }

            echo ">".GetNationalityDisplayName($value)."</option>\n";
        }

        echo "              </select>\n";
    }

    echo "              <input type=\"submit\" name=\"save\" value=\"".LANG_SUBMITBUTTON."\"/>\n".
         "            </fieldset>\n".
         "          </form>\n".
         "        </div>\n".
         "        <div>\n".
         "          <form action=\"persons.php\" method=\"post\">\n".
         "            <fieldset>\n".
         "              <input type=\"submit\" name=\"back\" value=\"".LANG_BACK."\"/>\n".
         "            </fieldset>\n".
         "          </form>\n".
         "        </div>\n";
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
