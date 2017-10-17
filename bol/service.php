<?php

class SPODDISCUSSION_BOL_Service
{
    private static $classInstance;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    // READER
    public function getCommentsByEntityId($entityId)
    {
        $sql = "SELECT T.id, T.entityId, T.ownerId, T.comment, T.timestamp,
                       ow_ode_datalet.component, ow_ode_datalet.data, ow_ode_datalet.fields, ow_ode_datalet.params, ow_ode_datalet.id 
                FROM (ow_spod_discussion_comment as T LEFT JOIN (SELECT dataletId, postId FROM ow_ode_datalet_post WHERE plugin = 'cocreation') as T1 on T.id = T1.postId) LEFT JOIN ow_ode_datalet ON T1.dataletId = ow_ode_datalet.id
                WHERE entityId = {$entityId}
                order by T.timestamp asc;";

        $dbo = OW::getDbo();

        return $dbo->queryForObjectList($sql,'SPODDISCUSSION_BOL_CommentContract');
    }

    // WRITER
    public function addComment($entityId, $comment, $ownerId)
    {
        $c = new SPODDISCUSSION_BOL_DiscussionComment();
        $c->entityId = $entityId;
        $c->comment = $comment;
        $c->ownerId = $ownerId;

        SPODDISCUSSION_BOL_DiscussionCommentDao::getInstance()->save($c);

        return $c;
    }

}
