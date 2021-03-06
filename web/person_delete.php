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
 * @file $/web/person_delete.php
 * @brief Delete a person.
 * @author Stephan Kreutzer
 * @since 2017-03-15
 */



require_once("./libraries/https.inc.php");
require_once("./libraries/session.inc.php");

require_once("./libraries/user_defines.inc.php");

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN)
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
else
{
    http_response_code(400);
    exit(-1);
}


require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("person_delete"));

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

if (!(isset($_POST['confirm']) === true))
{
    echo "        <div>\n".
         "          <p>".LANG_CONFIRMDELETETEXT."</p>\n".
         "          <form action=\"person_delete.php?id=".htmlspecialchars($personId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\" method=\"post\">\n".
         "            <fieldset>\n".
         "              <input type=\"submit\" name=\"confirm\" value=\"".LANG_CONFIRMBUTTON."\"/>\n".
         "            </fieldset>\n".
         "          </form>\n".
         "        </div>\n".
         "        <a href=\"persons.php\">".LANG_CANCEL."</a>\n";
}
else
{
    require_once(dirname(__FILE__)."/libraries/person_management.inc.php");

    $deleteSuccess = DeletePerson($personId);

    if ($deleteSuccess === 0)
    {
        echo "        <p>\n".
             "          <span class=\"success\">".LANG_OPERATIONSUCCEEDED."</span>\n".
             "        </p>\n".
             "        <a href=\"persons.php\">".LANG_DONE."</a>\n";
    }
    else
    {
        echo "        <p>\n".
             "          <span class=\"error\">".LANG_OPERATIONFAILED."</span>\n".
             "        </p>\n".
             "        <a href=\"persons.php\">".LANG_BACK."</a>\n";
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
