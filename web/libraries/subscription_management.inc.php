<?php
/* Copyright (C) 2017  Stephan Kreutzer
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
 * @file $/web/libraries/subscription_management.inc.php
 * @author Stephan Kreutzer
 * @since 2017-11-21
 */



require_once(dirname(__FILE__)."/database.inc.php");
require_once(dirname(__FILE__)."/event_type_defines.inc.php");



function GetSubscribers($personId)
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    $subscribers = Database::Get()->Query("SELECT `".Database::Get()->GetPrefix()."users`.`id`,\n".
                                          "    `".Database::Get()->GetPrefix()."users`.`e_mail`\n".
                                          "FROM `".Database::Get()->GetPrefix()."users`\n".
                                          "INNER JOIN `".Database::Get()->GetPrefix()."persons_subscribers` ON\n".
                                          "    `".Database::Get()->GetPrefix()."users`.`id` =\n".
                                          "    `".Database::Get()->GetPrefix()."persons_subscribers`.`id_user`\n".
                                          "WHERE `".Database::Get()->GetPrefix()."persons_subscribers`.`id_person`=?\n",
                                          array($personId),
                                          array(Database::TYPE_INT));

    if (is_array($subscribers) !== true)
    {
        return -2;
    }

    return $subscribers;
}

function GetSubscriptions($userId)
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    $subscriptions = Database::Get()->Query("SELECT `id_person`\n".
                                            "FROM `".Database::Get()->GetPrefix()."persons_subscribers`\n".
                                            "WHERE `id_user`=?\n",
                                            array($userId),
                                            array(Database::TYPE_INT));

    if (is_array($subscriptions) !== true)
    {
        return -2;
    }

    $result = array();

    if (count($subscriptions) > 0)
    {
        foreach ($subscriptions as $subscription)
        {
            $result[] = (int)$subscription['id_person'];
        }
    }

    return $result;
}

function SetSubscription($userId, $personId, $subscribe)
{
    if (Database::Get()->IsConnected() !== true)
    {
        return -1;
    }

    require_once(dirname(__FILE__)."/logging.inc.php");

    if (Database::Get()->BeginTransaction() !== true)
    {
        return -2;
    }

    $success = false;

    if ($subscribe === false)
    {
        if (logEvent(EVENT_DELETE, "PersonUnSubscribe", array($userId, $personId)) != 0)
        {
            Database::Get()->RollbackTransaction();
            return -3;
        }

        $success = Database::Get()->Execute("DELETE\n".
                                            "FROM `".Database::Get()->GetPrefix()."persons_subscribers`\n".
                                            "WHERE `id_person`=? AND\n".
                                            "    `id_user`=?",
                                            array($personId, $userId),
                                            array(Database::TYPE_INT, Database::TYPE_INT));
    }
    else
    {
        if (logEvent(EVENT_CREATE, "PersonSubscribe", array($userId, $personId)) != 0)
        {
            Database::Get()->RollbackTransaction();
            return -4;
        }

        $success = Database::Get()->Insert("INSERT INTO `".Database::Get()->GetPrefix()."persons_subscribers` (`id_person`,\n".
                                           "    `id_user`)\n".
                                           "VALUES (?, ?)\n",
                                           array($userId, $personId),
                                           array(Database::TYPE_INT, Database::TYPE_INT));
    }

    if ($success !== true)
    {
        Database::Get()->RollbackTransaction();
        return -5;
    }

    if (Database::Get()->CommitTransaction() !== true)
    {
        return -6;
    }

    return 0;
}

function SendSubscribedNotification($crud, $name, $personId)
{
    if (file_exists(dirname(__FILE__)."/../custom/notify.inc.php") !== true)
    {
        return;
    }

    require_once(dirname(__FILE__)."/../custom/notify.inc.php");

    $time = date("c");
    $subscribers = GetSubscribers($personId);

    if (is_array($subscribers) !== true)
    {
        return;
    }

    if (count($subscribers) <= 0)
    {
        return;
    }

    foreach ($subscribers as $subscriber)
    {
        if (isset($_SESSION['user_id']) === true)
        {
            if ((int)$subscriber['id'] === (int)$_SESSION['user_id'])
            {
                continue;
            }
        }

        $message = "Time: ".$time."\n".
                   "Event: ".htmlspecialchars($name, ENT_COMPAT | ENT_HTML401, "UTF-8")."\n".
                   "Person: ".htmlspecialchars($personId, ENT_COMPAT | ENT_HTML401, "UTF-8")."\n";

        @mail($subscriber['e_mail'],
              GetNotificationSubject($crud, $name),
              $message,
              "From: ".GetNotificationSender()."\n".
              "MIME-Version: 1.0\n".
              "Content-type: text/plain; charset=UTF-8\n");
    }
}


?>
