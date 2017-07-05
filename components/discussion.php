<?php

class SPODDISCUSSION_CMP_Discussion extends OW_Component
{
    private $avatar_colors =    ['avatar_pink', 'avatar_purple', 'avatar_deeppurple', 'avatar_indigo',
        'avatar_lightblue', 'avatar_teal', 'avatar_lightgreen', 'avatar_lime',
        'avatar_yellow', 'avatar_amber', 'avatar_deeporange',
        'avatar_brown', 'avatar_grey', 'avatar_bluegrey'];
    private $avatars;
    private $userId;

    public function __construct($entityId)
    {
        $this->userId = OW::getUser()->getId();

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('spoddiscussion')->getStaticJsUrl() . 'discussion.js');
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('spoddiscussion')->getStaticJsUrl() . 'discussionJs.js');
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('spoddiscussion')->getStaticJsUrl() . 'socket_1_7_3.io.js');
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('spoddiscussion')->getStaticJsUrl() . 'perfect-scrollbar.jquery.js');
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('spoddiscussion')->getStaticCssUrl() . 'perfect-scrollbar.min.css');
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('spoddiscussion')->getStaticCssUrl() . 'spod_discussion.css');
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('spoddiscussion')->getStaticJsUrl() . 'jquery.cssemoticons.min.js');
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('spoddiscussion')->getStaticCssUrl() . 'jquery.cssemoticons.css');
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('spoddiscussion')->getStaticJsUrl() . 'autogrow.min.js');

        $raw_comments = SPODDISCUSSION_BOL_Service::getInstance()->getCommentsByEntityId($entityId);
        $this->assign('comments', $this->process_comment_include_datalet($raw_comments, OW::getUser()->getId()));

        // ADD PRIVATE ROOM DEFINITION
        $this->assign('components_url', SPODPR_COMPONENTS_URL);

        $this->initializeJS($entityId);
    }

    private function initializeJS($entityId)
    {
        $avatars = $this->avatars;

        if(empty($avatars[$this->userId]))
        {
            $avatars = $this->process_avatar(BOL_AvatarService::getInstance()->getDataForUserAvatars(array($this->userId)));
        }

        $js = UTIL_JsGenerator::composeJsString('
            SPODDISCUSSION.entityId = {$entityId}
            SPODDISCUSSION.comment_endpoint = {$comment_endpoint}
            SPODDISCUSSION.userId = {$userId}
            SPODDISCUSSION.username = {$username}
            SPODDISCUSSION.user_url = {$user_url}
            SPODDISCUSSION.user_avatar_src = {$user_avatar_src}
            SPODDISCUSSION.user_avatar_css = {$user_avatar_css}
            SPODDISCUSSION.user_avatar_initial = {$user_avatar_initial}
            SPODDISCUSSION.static_resource_url = {$static_resource_url}
            ',
            array(
                'entityId' => $entityId,
                'comment_endpoint' => OW::getRouter()->urlFor('SPODDISCUSSION_CTRL_Ajax', 'addComment'),
                'userId' => $this->userId ,
                'username' => $avatars[$this->userId]["title"],
                'user_url' => $avatars[$this->userId]["url"],
                'user_avatar_src' => $avatars[$this->userId]["src"],
                'user_avatar_css' => $avatars[$this->userId]["css"],
                'user_avatar_initial' => $avatars[$this->userId]["initial"],
                'static_resource_url' => OW::getPluginManager()->getPlugin('spoddiscussion')->getStaticUrl()
        ));

        OW::getDocument()->addOnloadScript($js);

        OW::getLanguage()->addKeyForJs('spodagora', 'empty_message');
        OW::getLanguage()->addKeyForJs('spodagora', 'c_just_now');
        OW::getLanguage()->addKeyForJs('spodagora', 'c_reply');
        OW::getLanguage()->addKeyForJs('spodagora', 't_delete');
        OW::getLanguage()->addKeyForJs('spodagora', 't_modify');
        OW::getLanguage()->addKeyForJs('spodagora', 'g_datalets');
        OW::getLanguage()->addKeyForJs('spodagora', 'g_datasets');
        OW::getLanguage()->addKeyForJs('spodagora', 'g_time');

        //OW::getDocument()->addOnloadScript('SPODDISCUSSION.init();');
    }

    public function process_comment_include_datalet(&$comments, $user_id)
    {
        $users_ids      = array_map(function($comments) { return $comments->ownerId;}, $comments);
        $this->avatars  = $this->process_avatar(BOL_AvatarService::getInstance()->getDataForUserAvatars($users_ids));

        $today = date('Ymd');
        $yesterday = date('Ymd', strtotime('yesterday'));

        foreach ($comments as &$comment)
        {
            $comment->username       = $this->avatars[$comment->ownerId]["title"];
            $comment->owner_url      = $this->avatars[$comment->ownerId]["url"];
            $comment->avatar_url     = $this->avatars[$comment->ownerId]["src"];
            $comment->avatar_css     = $this->avatars[$comment->ownerId]["css"];
            $comment->avatar_initial = $this->avatars[$comment->ownerId]["initial"];
            $comment->timestamp      = $this->process_timestamp($comment->timestamp, $today, $yesterday);

            $comment->css_class       = $user_id == $comment->ownerId ? 'agora_right_comment' : 'agora_left_comment';
            $comment->datalet_class   = '';
            $comment->datalet_html    = '';

            if (isset($comment->component)) {
                $comment->datalet_class = 'agora_fullsize_datalet';
                $comment->datalet_html  = $this->create_datalet_code($comment);
            }else{
                $comment->component = '';
            }

        }

        return $comments;
    }

    public function process_avatar($avatars)
    {
        if (empty($avatars))
            return;

        foreach ($avatars as &$avatar)
        {
            if(strpos( $avatar['src'], 'no-avatar'))
            {
                $avatar['css'] = 'no_img ' . $this->avatar_colors[$avatar["userId"] % count($this->avatar_colors)];
                $avatar['initial'] = strtoupper($avatar['title'][0]);
                $avatar['src'] = '';
            }
            else
            {
                $avatar['css'] = '';
                $avatar['initial'] = '';
            }

        }

        return $avatars;
    }

    public function process_timestamp($timestamp, $today, $yesterday)
    {
        $date = date('Ymd', strtotime($timestamp));

        if($date == $today)
            return date('H:i', strtotime($timestamp));

        if($date == $yesterday)
            return OW::getLanguage()->text('spodagora', 'yesterday'). " " . date('H:i', strtotime($timestamp));

        return date('H:i m/d', strtotime($timestamp));
    }

    private function create_datalet_code($comment)
    {
        $params = json_decode($comment->params);
        $html  = '';//"<link rel='import' href='".SPODPR_COMPONENTS_URL."datalets/{$comment->component}/{$comment->component}.html' />";
        $html .= "<{$comment->component} ";

        foreach ($params as $key => $value){
            $html .= $key."='".$this->htmlSpecialChar($value)."' ";
        }

        //CACHE
        $html .= " data='{$comment->data}'";
        $html .= " ></{$comment->component}>";

        return $html;
    }

    protected function htmlSpecialChar($string)
    {
        return str_replace("'","&#39;", $string);
    }
}