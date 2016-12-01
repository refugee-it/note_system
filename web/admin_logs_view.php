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
 * @file $/web/admin_logs_view.php
 * @brief Views the logs.
 * @author Stephan Kreutzer
 * @since 2012-06-01
 */



require_once("./libraries/https.inc.php");

session_start();

if (isset($_SESSION['user_id']) !== true)
{
    exit(-1);
}

require_once(dirname(__FILE__)."/libraries/user_defines.inc.php");

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN)
{
    exit(-1);
}

require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("admin_logs_view"));

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
     "<!DOCTYPE html\n".
     "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
     "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
     "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:tsorter=\"http://www.terrill.ca/sorting\" xml:lang=\"".getCurrentLanguage()."\" lang=\"".getCurrentLanguage()."\">\n".
     "  <head>\n".
     "    <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>\n".
     "    <title>".LANG_PAGETITLE."</title>\n".
     "    <link rel=\"stylesheet\" type=\"text/css\" href=\"mainstyle.css\"/>\n".
     "    <script type=\"text/javascript\" src=\"tsorter.js\"></script>\n".
     "    <script type=\"text/javascript\">\n".
     "      window.onload = function() {\n".
     "        tsorter.create('logs_table', 0);\n".
     "      };\n".
     "    </script>\n".
     "  </head>\n".
     "  <body>\n".
     "    <div class=\"mainbox\">\n".
     "      <div class=\"mainbox_header\">\n".
     "        <h1 class=\"mainbox_header_h1\">".LANG_HEADER."</h1>\n".
     "      </div>\n".
     "      <div class=\"mainbox_body\">\n";

require_once("./libraries/database.inc.php");

if (Database::Get()->IsConnected() !== true)
{
        return -1;
}

$logs = Database::Get()->QueryUnsecure("SELECT `".Database::Get()->GetPrefix()."logs`.`id`,\n".
                                       "    `".Database::Get()->GetPrefix()."logs`.`datetime`,\n".
                                       "    `".Database::Get()->GetPrefix()."logs`.`text`,\n".
                                       "    `".Database::Get()->GetPrefix()."users`.`name` AS `user_name`\n".
                                       "FROM `".Database::Get()->GetPrefix()."logs`\n".
                                       "INNER JOIN `".Database::Get()->GetPrefix()."users`\n".
                                       "ON `".Database::Get()->GetPrefix()."logs`.`id_user`=`".Database::Get()->GetPrefix()."users`.`id`\n".
                                       "WHERE 1");

if (is_array($logs) === true)
{
    if (count($logs) > 0)
    {
        echo "        <table id=\"logs_table\">\n".
             "          <thead>\n".
             "            <tr>\n".
             "              <th tsorter:data-tsorter=\"numeric\">".LANG_TABLECOLUMNCAPTION_ID."</th>\n".
             "              <th tsorter:data-tsorter=\"date\">".LANG_TABLECOLUMNCAPTION_TIMESTAMP."</th>\n".
             "              <th tsorter:data-tsorter=\"default\">".LANG_TABLECOLUMNCAPTION_TEXT."</th>\n".
             "              <th tsorter:data-tsorter=\"default\">".LANG_TABLECOLUMNCAPTION_USERNAME."</th>\n".
             "            </tr>\n".
             "          </thead>\n".
             "          <tbody>\n";

        foreach ($logs as $log)
        {
            echo "            <tr>\n".
                 "              <td>".htmlspecialchars($log['id'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n".
                 "              <td>".htmlspecialchars($log['datetime'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n".
                 "              <td>".htmlspecialchars($log['text'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n".
                 "              <td>".htmlspecialchars($log['user_name'], ENT_COMPAT | ENT_HTML401, "UTF-8")."</td>\n".
                 "            </tr>\n";
        }

        echo "          </tbody>\n".
             "        </table>\n";
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