<?php

class SPODDISCUSSION_CMP_Discussion extends OW_Component
{
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
        $this->assign('comments', SPODDISCUSSION_CLASS_Tools::getInstance()->process_comment_include_datalet($raw_comments, OW::getUser()->getId()));

        // ADD PRIVATE ROOM DEFINITION
        $this->assign('components_url', SPODPR_COMPONENTS_URL);

        $this->initializeJS($entityId);
    }

    private function initializeJS($entityId)
    {
        $avatars = SPODDISCUSSION_CLASS_Tools::getInstance()->process_avatar(BOL_AvatarService::getInstance()->getDataForUserAvatars(array($this->userId)));

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
}