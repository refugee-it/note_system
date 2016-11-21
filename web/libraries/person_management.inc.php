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
 * @file $/web/libraries/person_management.inc.php
 * @author Stephan Kreutzer
 * @since 2016-07-25
 */



require_once(dirname(__FILE__)."/database.inc.php");



function GetPersons()
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    $persons = Database::Get()->QueryUnsecure("SELECT `id`,\n".
                                              "    `family_name`,\n".
                                              "    `given_name`,\n".
                                              "    `date_of_birth`,\n".
                                              "    `location`,\n".
                                              "    `nationality`\n".
                                              "FROM `".Database::Get()->GetPrefix()."persons`\n".
                                              "WHERE 1");

    if (is_array($persons) === true)
    {
        return $persons;
    }

    return null;
}



?>
