<?php

class SPODDISCUSSION_CLASS_Tools
{
    private $avatar_colors =    ['avatar_pink', 'avatar_purple', 'avatar_deeppurple', 'avatar_indigo',
        'avatar_lightblue', 'avatar_teal', 'avatar_lightgreen', 'avatar_lime',
        'avatar_yellow', 'avatar_amber', 'avatar_deeporange',
        'avatar_brown', 'avatar_grey', 'avatar_bluegrey'];

    private static $classInstance;

    public static function getInstance()
    {
        if(self::$classInstance === null)
            self::$classInstance = new self();

        return self::$classInstance;
    }

    public function process_comment_include_datalet(&$comments, $user_id)
    {
        $users_ids      = array_map(function($comments) { return $comments->ownerId;}, $comments);
        $avatars  = $this->process_avatar(BOL_AvatarService::getInstance()->getDataForUserAvatars($users_ids));

        $today = date('Ymd');
        $yesterday = date('Ymd', strtotime('yesterday'));

        foreach ($comments as &$comment)
        {
            $comment->username       = $avatars[$comment->ownerId]["title"];
            $comment->owner_url      = $avatars[$comment->ownerId]["url"];
            $comment->avatar_url     = $avatars[$comment->ownerId]["src"];
            $comment->avatar_css     = $avatars[$comment->ownerId]["css"];
            $comment->avatar_initial = $avatars[$comment->ownerId]["initial"];
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