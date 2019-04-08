<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/8
 * Time: 5:50 PM
 */

namespace Im050\WeChat\Message\Formatter;


class GroupSys extends SysMessage
{

    const ACTION_INVITE = "INVITE";
    const ACTION_ADD = "ADD";
    const ACTION_REMOVE = "REMOVE";
    const ACTION_RENAME = "RENAME";
    const ACTION_BE_REMOVE = "BE_REMOVE";

    private $action;
    private $inviter;
    private $invitee;

    public function handleMessage()
    {
        $this->settingByContent();
    }

    /**
     * via Vbot.
     */
    private function settingByContent() {
        if (str_contains($this->content, '邀请你')) {
            $this->setAction(self::ACTION_INVITE);
        } elseif (str_contains($this->content, '加入了群聊') || str_contains($this->content, '分享的二维码加入群聊')) {
            $isMatch = preg_match('/"?(.+)"?邀请"(.+)"加入了群聊/', $this->content, $match);
            if ($isMatch) {
                $this->inviter = $match[1];
                $this->invited = $match[2];
            } else {
                preg_match('/"(.+)"通过扫描"?(.+)"?分享的二维码加入群聊/', $this->content, $match);
                $this->inviter = $match[2];
                $this->invited = $match[1];
            }
            $this->setAction(self::ACTION_ADD);
        } elseif (str_contains($this->content, '移出了群聊')) {
            $this->setAction(self::ACTION_REMOVE);
        } elseif (str_contains($this->content, '改群名为')) {
            $this->setAction(self::ACTION_RENAME);
        } elseif (str_contains($this->content, '移出群聊')) {
            $this->setAction(self::ACTION_BE_REMOVE);
        }
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return mixed
     */
    public function getInviter()
    {
        return $this->inviter;
    }

    /**
     * @param mixed $inviter
     */
    public function setInviter($inviter)
    {
        $this->inviter = $inviter;
    }

    /**
     * @return mixed
     */
    public function getInvitee()
    {
        return $this->invitee;
    }

    /**
     * @param mixed $invitee
     */
    public function setInvitee($invitee)
    {
        $this->invitee = $invitee;
    }
}