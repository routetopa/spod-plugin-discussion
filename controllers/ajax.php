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

        /*if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }*/

        if (!OW::getUser()->isAuthenticated())
        {
            try
            {
                $user_id = ODE_CLASS_Tools::getInstance()->getUserFromJWT($_REQUEST['jwt']);
            }
            catch (Exception $e)
            {
                echo json_encode(array("status"  => "ko", "error_message" => $e->getMessage()));
                exit;
            }
        }else{
            $user_id = OW::getUser()->getId();
        }

        if(SPODAGORA_CLASS_Tools::getInstance()->check_value(["entityId", "comment"]))
        {
            // Change \n to <br> for correct visualization of new line in HTML
            $comment  = str_replace("\n", "<br/>", htmlentities($_REQUEST['comment']));

            $c = SPODDISCUSSION_BOL_Service::getInstance()->addComment($_REQUEST['entityId'],
                $comment,
                $user_id);

            $this->send_realtime_notification($c, $user_id);

            /* ODE */
            if( ODE_CLASS_Helper::validateDatalet($_REQUEST['datalet']['component'], $_REQUEST['datalet']['params'], $_REQUEST['datalet']['fields']) )
            {
                ODE_BOL_Service::getInstance()->addDatalet(
                    $_REQUEST['datalet']['component'],
                    $_REQUEST['datalet']['fields'],
                    $user_id,
                    $_REQUEST['datalet']['params'],
                    $c->getId(),
                    $_REQUEST['plugin'],
                    $_REQUEST['datalet']['data']);
            }
            /* ODE */

            if (!empty($c->id)) {
                //Add comment event trigger for notification system
                OW::getEventManager()->trigger(new OW_Event('spod_discussion.add_comment', array('comment' => $c)));
                echo '{"result":"ok", "post_id":"' . $c->id . '"}';
            }
            else
                echo '{"result":"ko"}';
        }
        else
        {
            echo '{"result":"ko"}';
        }

        exit;
    }

    //Reader
    public function getComments()
    {
        try
        {
            $user_id = ODE_CLASS_Tools::getInstance()->getUserFromJWT($_REQUEST['jwt']);
        }
        catch (Exception $e)
        {
            echo json_encode(array("status"  => "ko", "error_message" => $e->getMessage()));
            exit;
        }

        $raw_comments = SPODDISCUSSION_BOL_Service::getInstance()->getCommentsByEntityId($_REQUEST['entityId']);
        $comments = SPODDISCUSSION_CLASS_Tools::getInstance()->process_comment_include_datalet($raw_comments, $user_id);
        echo json_encode($comments);
        exit;
    }

    //Realtime
    private function send_realtime_notification($comment, $user_id)
    {
        try
        {
            $client = new Client(new Version1X('http://localhost:3000/realtime_notification'));
            $client->initialize();

            $avatar_data = SPODDISCUSSION_CLASS_Tools::getInstance()->get_avatar_data($comment->ownerId);

            $client->emit('realtime_notification',
                ['plugin' => 'cocreation_discussion',
                'entityId' => $_REQUEST['entityId'],
                'comment' => $comment->comment,
                'message_id' => $comment->id,
                'user_id' => $user_id,
                'user_display_name' => $avatar_data['username'],
                'user_avatar' => $avatar_data['user_avatar_src'],
                'user_avatar_css' => $avatar_data['user_avatar_css'],
                'user_avatar_initial' => $avatar_data['user_avatar_initial'],
                'user_url' => $_REQUEST['user_url'],
                'component' => empty($_REQUEST['datalet']['component']) ? '' : $_REQUEST['datalet']['component'],
                'params' => empty($_REQUEST['datalet']['params']) ? '' : $_REQUEST['datalet']['params'],
                'fields' => empty($_REQUEST['datalet']['fields']) ? '' : $_REQUEST['datalet']['fields'],
                'data' => empty($_REQUEST['datalet']['data']) ? '' : $_REQUEST['datalet']['data'] ]);

            $client->close();
        }
        catch(Exception $e)
        {

        }
    }



}