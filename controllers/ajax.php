<?php

require_once OW::getPluginManager()->getPlugin('spodnotification')->getRootDir()
    . 'lib/vendor/autoload.php';

use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

class SPODDISCUSSION_CTRL_Ajax extends OW_ActionController
{
    //Writer
    public function addComment()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect403Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        if(SPODAGORA_CLASS_Tools::getInstance()->check_value(["entityId", "comment"]))
        {
            // Change \n to <br> for correct visualization of new line in HTML
            $comment  = str_replace("\n", "<br/>", htmlentities($_REQUEST['comment']));

            $c = SPODDISCUSSION_BOL_Service::getInstance()->addComment($_REQUEST['entityId'],
                $comment,
                OW::getUser()->getId());

            $this->send_realtime_notification($c);

            /* ODE */
            if( ODE_CLASS_Helper::validateDatalet($_REQUEST['datalet']['component'], $_REQUEST['datalet']['params'], $_REQUEST['datalet']['fields']) )
            {
                ODE_BOL_Service::getInstance()->addDatalet(
                    $_REQUEST['datalet']['component'],
                    $_REQUEST['datalet']['fields'],
                    OW::getUser()->getId(),
                    $_REQUEST['datalet']['params'],
                    $c->getId(),
                    $_REQUEST['plugin'],
                    $_REQUEST['datalet']['data']);
            }
            /* ODE */

            if (!empty($c->id))
                echo '{"result":"ok", "post_id":"'.$c->id.'"}';
            else
                echo '{"result":"ko"}';
        }
        else
        {
            echo '{"result":"ko"}';
        }

        exit;
    }


    //Realtime
    private function send_realtime_notification($comment)
    {
        try
        {
            $client = new Client(new Version1X('http://localhost:3000/realtime_notification'));
            $client->initialize();

            $client->emit('realtime_notification',
                ['plugin' => 'cocreation_discussion',
                'entityId' => $_REQUEST['entityId'],
                'comment' => $comment->comment,
                'message_id' => $comment->id,
                'user_id' => OW::getUser()->getId(),
                'user_display_name' => $_REQUEST['username'],
                'user_avatar' => $_REQUEST['user_avatar_src'],
                'user_avatar_css' => $_REQUEST['user_avatar_css'],
                'user_avatar_initial' => $_REQUEST['user_avatar_initial'],
                'user_url' => $_REQUEST['user_url'],
                'component' => $_REQUEST['datalet']['component'],
                'params' => $_REQUEST['datalet']['params'],
                'fields' => $_REQUEST['datalet']['fields'],
                'data' => $_REQUEST['datalet']['data']]);

            $client->close();
        }
        catch(Exception $e)
        {

        }
    }



}