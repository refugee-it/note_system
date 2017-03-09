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
 * @file $/web/note_details.php
 * @brief View details of a note.
 * @author Stephan Kreutzer
 * @since 2014-06-08
 */



require_once("./libraries/https.inc.php");

session_start();

if (isset($_SESSION['user_id']) !== true)
{
    header("HTTP/1.1 403 Forbidden");
    exit(-1);
}

require_once("./libraries/user_defines.inc.php");

if ((int)$_SESSION['user_role'] !== USER_ROLE_ADMIN &&
    (int)$_SESSION['user_role'] !== USER_ROLE_USER)
{
    header("HTTP/1.1 403 Forbidden");
    exit(-1);
}

$noteId = null;

if (isset($_POST['id']) === true)
{
    if (is_numeric($_POST['id']) === true)
    {
        $noteId = (int)$_POST['id'];
    }
}

if (isset($_GET['id']) === true)
{
    if (is_numeric($_GET['id']) === true)
    {
        $noteId = (int)$_GET['id'];
    }
}

if ($noteId == null)
{
    header("HTTP/1.1 400 Bad Request");
    exit(-1);
}



require_once("./libraries/languagelib.inc.php");
require_once(getLanguageFile("note_details"));
require_once("./libraries/note_management.inc.php");


echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
     "<!DOCTYPE html\n".
     "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
     "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
     "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"".getCurrentLanguage()."\" lang=\"".getCurrentLanguage()."\">\n".
     "  <head>\n".
     "    <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>\n".
     "    <title>".LANG_PAGETITLE."</title>\n".
     "    <link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"mainstyle.css\"/>\n".
     "    <link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"mainstyle_print.css\"/>\n".
     "    <style type=\"text/css\">\n".
     "      .th, .td\n".
     "      {\n".
     "          padding: 0px 10px 0px 0px;\n".
     "      }\n".
     "\n".
     "      .urgent\n".
     "      {\n".
     "          color: red;\n".
     "      }\n".
     "    </style>\n".
     "  </head>\n".
     "  <body>\n".
     "    <div class=\"mainbox\">\n".
     "      <div class=\"mainbox_header\">\n".
     "        <h1 class=\"mainbox_header_h1\">".LANG_HEADER."</h1>\n".
     "      </div>\n".
     "      <div class=\"mainbox_body\">\n".
     "        <div>\n";

if (isset($_POST['assign']) === true)
{
    $success = NoteAssignUser($noteId, $_SESSION['user_id']);

    if ($success !== 0)
    {
        echo "          <p class=\"error\">\n".
             "            ".LANG_OPERATIONFAILED."\n".
             "          </p>\n";
    }
}
else if (isset($_POST['unassign']) === true)
{
    $success = NoteDeAssignUser($noteId, $_SESSION['user_id']);

    if ($success !== 0)
    {
        echo "          <p class=\"error\">\n".
             "            ".LANG_OPERATIONFAILED."\n".
             "          </p>\n";
    }
}
else if (isset($_POST['completed']) === true)
{
    $success = NoteCompleted($noteId, $_SESSION['user_id']);

    if ($success !== 0)
    {
        echo "          <p class=\"error\">\n".
             "            ".LANG_OPERATIONFAILED."\n".
             "          </p>\n";
    }
}

$note = GetNoteById($noteId);

if (is_array($note) === true)
{
    if ((int)$note['status'] == NOTE_STATUS_ACTIVE ||
        (int)$_SESSION['user_role'] == USER_ROLE_ADMIN)
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

        echo "          <div class=\"table\">\n".
             "            <div class=\"tr\">\n".
             "              <span class=\"th\">".LANG_CAPTION_NOTEPRIORITY."</span> <span class=\"td\">".$note['priority']."</span>\n";

        if (array_key_exists((int)$note['category'], $categoriesCached) === true)
        {
            echo "              <span class=\"th\">".LANG_CAPTION_NOTECATEGORY."</span> <span class=\"td\">".GetNoteCategoryDisplayNameById($note['category'])."</span>\n";
        }
        else
        {
            echo "              <span class=\"th\">".LANG_CAPTION_NOTECATEGORY."</span> <span class=\"td\">".$note['category']."</span>\n";
        }

        echo "              <span class=\"th\">".LANG_CAPTION_NOTEASSIGNED." </span>\n".
             "              <span class=\"td\">\n";

        if ((int)$note['status'] === NOTE_STATUS_ACTIVE)
        {
            if (is_numeric($note['id_user_assigned']) === true)
            {
                if ((int)$note['id_user_assigned'] === (int)$_SESSION['user_id'])
                {
                    echo "                <form action=\"note_details.php\" method=\"post\">\n".
                        "                  <input type=\"hidden\" name=\"id\" value=\"".$noteId."\"/>\n".
                        "                  <input type=\"submit\" name=\"unassign\" value=\"".LANG_UNASSIGNBUTTON."\" class=\"noprint\"/>\n".
                        "                  <input type=\"submit\" name=\"completed\" value=\"".LANG_COMPLETEDBUTTON."\" class=\"noprint\"/>\n".
                        "                </form>\n";
                }
                else
                {
                    echo "                ".htmlspecialchars($note['user_assigned_name'], ENT_COMPAT | ENT_HTML401, "UTF-8")."\n";
                }
            }
            else
            {
                echo "                <form action=\"note_details.php\" method=\"post\">\n".
                    "                  <input type=\"hidden\" name=\"id\" value=\"".$noteId."\"/>\n".
                    "                  <input type=\"submit\" name=\"assign\" value=\"".LANG_ASSIGNBUTTON."\" class=\"noprint\"/>\n".
                    "                </form>\n";
            }
        }

        echo "              </span>\n".
             "            </div>\n".
             "            <div class=\"tr\">\n".
             "              <span class=\"th\">".LANG_CAPTION_NOTEOWNER."</span> <span class=\"td\">".$note['user_name']."</span>\n".
             "              <span class=\"th\">".LANG_CAPTION_NOTEMODIFIED."</span> <span class=\"td\">".$note['datetime_modified']."</span>\n".
             "              <span class=\"th\"></span> <span class=\"td\"></span>\n".
             "            </div>\n".
             "          </div>\n";

        $flags = (int)$note['flags'];
        $flagsString = "";

        if ((int)$note['status'] === NOTE_STATUS_COMPLETED)
        {
            if (!empty($flagsString))
            {
                $flagsString .= ", ";
            }

            $flagsString .= LANG_STATUSCOMPLETED;
        }

        if (($flags & NOTE_FLAGS_URGENT) === NOTE_FLAGS_URGENT)
        {
            if (!empty($flagsString))
            {
                $flagsString .= ", ";
            }

            $flagsString .= "<span class=\"urgent\">".LANG_FLAGURGENT."</span>";
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

        if (!empty($flagsString))
        {
            echo "          <div>\n".
                 "            <span class=\"th\">".LANG_CAPTION_NOTEMARKINGS."</span> <span class=\"td\">".$flagsString."</span>\n".
                 "          </div>\n";
        }

        echo "          <p>\n".
             "            ".htmlspecialchars($note['text'], ENT_COMPAT | ENT_HTML401, "UTF-8")."\n".
             "          </p>\n";

        $uploadsText = "";
        $uploads = GetNoteUploads($noteId);

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

                    if ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN &&
                        (int)$upload['status'] === NOTE_UPLOAD_STATUS_TRASHED)
                    {
                        $uploadsText .= "</span>";
                    }

                    $uploadsText .= " <a href=\"note_file_download.php?id=".$upload['id']."\" class=\"noprint\">".LANG_LINKCAPTION_DOWNLOADFILE."</a> ";

                    if ((int)$note['status'] === NOTE_STATUS_ACTIVE &&
                        ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN ||
                         ((int)$_SESSION['user_role'] === USER_ROLE_USER &&
                          (int)$_SESSION['user_id'] === (int)$note['id_user'])))
                    {
                        $uploadsText .= "<a href=\"note_file_delete.php?id=".$upload['id']."\" class=\"noprint\">".LANG_LINKCAPTION_DELETEFILE."</a> ";
                    }

                    $uploadsText .= "</li>\n";
                }
            }
        }

        if (((int)$note['status'] === NOTE_STATUS_ACTIVE &&
             ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN ||
              ((int)$_SESSION['user_role'] === USER_ROLE_USER &&
               (int)$_SESSION['user_id'] === (int)$note['id_user']))))
        {
            $uploadsText = $uploadsText.
                           "              <li class=\"noprint\"><a href=\"note_file_upload.php?id=".((int)$note['id'])."\" class=\"noprint\">".LANG_LINKCAPTION_UPLOADFILE."</a></li>\n";
        }

        if (empty($uploadsText) !== true)
        {
            echo "          <div>\n".
                 "            <p>\n".
                 "              ".LANG_ATTACHED_FILES."\n".
                 "            </p>\n".
                 "            <ul>\n".
                 $uploadsText.
                 "            </ul>\n".
                 "          </div>\n";
        }
    }

    if ((int)$note['status'] === NOTE_STATUS_ACTIVE &&
        ((int)$_SESSION['user_role'] === USER_ROLE_ADMIN ||
         ((int)$_SESSION['user_role'] === USER_ROLE_USER &&
          (int)$_SESSION['user_id'] === (int)$note['id_user'])))
    {
        echo "          <a href=\"note_update.php?id=".((int)$note['id'])."\" class=\"noprint\">".LANG_LINKCAPTION_UPDATENOTE."</a>\n".
             "          <a href=\"note_delete.php?id=".((int)$note['id'])."\" class=\"noprint\">".LANG_LINKCAPTION_DELETENOTE."</a>\n";
    }
}
else
{
    /** @todo Error message. */
}

echo "          <a href=\"person_details.php?id=".((int)$note['id_person'])."\" class=\"noprint\">".LANG_LINKCAPTION_PERSON."</a>\n".
     "        </div>\n".
     "      </div>\n".
     "    </div>\n".
     "    <div class=\"footerbox\">\n".
     "      <a href=\"license.php\" class=\"footerbox_link\">".LANG_LICENSE."</a>\n".
     "    </div>\n".
     "  </body>\n".
     "</html>\n";




?>
