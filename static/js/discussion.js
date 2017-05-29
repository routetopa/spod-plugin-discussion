SPODDISCUSSION = {
    discussionJs:null,
    initialized:false
};

SPODDISCUSSION.init = function ()
{
    if(SPODDISCUSSION.initialized)
        return;

    SPODDISCUSSION.initialized = true;

    SPODDISCUSSION.initDiscussionJs();

    // Set datalet preview target
    ODE.commentTarget = "agora_datalet_preview";

    // Set plugin preview to 'agora'
    ODE.pluginPreview = 'agora';

    // Handle for click on send button (submit message)
    $("#agora_comment_send").click(function(){
        if(!SPODDISCUSSION.discussionJs.submit())
            OW.error("Messaggio vuoto");
    });

    //Handler datalet creator button
    $('#agora_controllet_button').click(function(){
        previewFloatBox = OW.ajaxFloatBox('ODE_CMP_Preview', {} , {top:'56px', width:'calc(100vw - 112px)', height:'calc(100vh - 112px)', iconClass: 'ow_ic_add', title: ''});
    });

    //Handler mySpace button
    $('#agora_myspace_button').click(function(){
        previewFloatBox = OW.ajaxFloatBox('SPODPR_CMP_PrivateRoomCardViewer', {data:['datalet']}, {top:'56px', width:'calc(100vw - 112px)', height:'calc(100vh - 112px)', iconClass: 'ow_ic_add', title: ''});
    });

    // Handle preview
    $("#agora_preview_button").click(function () {
        SPODDISCUSSION.onPreviewButtonClick();
    });

    // Handler realtime notification (socket.io)
    SPODDISCUSSION.handleRealtimeNotification();

    // Handler for comment added
    $(window).on("comment_added", function(e){
        SPODDISCUSSION.onCommentAdded(e)
    });

    // Handler for document ready (init perfectScrollbar, resize page, init autogrow)
    $(document).ready(function () {
        SPODDISCUSSION.documentReady();
    });

};

SPODDISCUSSION.documentReady = function()
{
    $('.agora_speech_text').emoticonize();
    $("#agora_chat_container").scrollTop($("#agora_chat_container").prop("scrollHeight"));
    $("#agora_chat_container").perfectScrollbar();
    $("#agora_comment").autogrow();
};

SPODDISCUSSION.initDiscussionJs = function ()
{
    SPODDISCUSSION.discussionJs = new discussionJs();
    SPODDISCUSSION.discussionJs.init($("#agora_comment"),SPODDISCUSSION.entityId, SPODDISCUSSION.comment_endpoint);
    SPODDISCUSSION.discussionJs.set_string_handler(SPODDISCUSSION.string_handler);
};

SPODDISCUSSION.string_handler = function(string)
{
    return string.replace(/\n/g, "<br/>");
};

SPODDISCUSSION.onPreviewButtonClick = function ()
{
    var elem = $("#agora_datalet_preview");
    elem.toggle();
    try {
        var e = elem.children()[0];
        $(e).context.behavior.redraw();
    }catch (e) {}
};

SPODDISCUSSION.handleRealtimeNotification = function ()
{
    // Handle realtime communication
    var socket = io(window.location.origin , {path: "/realtime_notification"/*, transports: [ 'polling' ]*/});
    var target = $("#agora_chat_container");

    socket.on('realtime_message_' + SPODDISCUSSION.entityId, function(data) {

        if (SPODDISCUSSION.userId !== data.user_id)
        {
            SPODDISCUSSION.discussionJs.add_rt_comment(
                SPODDISCUSSION.static_resource_url + 'JSSnippet/rt_comment.tpl',
                [data.message_id,
                    data.user_avatar_css,
                    data.user_display_name,
                    data.user_url,
                    data.user_avatar,
                    data.user_avatar_initial,
                    data.comment,
                    data.message_id,
                    data.user_display_name,
                    OW.getLanguageText('spodagora', 'c_just_now')
                ],
                {component: data.component, params: data.params, fields: data.fields, data: data.data},
                data.message_id,
                target
            );
        }
    });
};

SPODDISCUSSION.onCommentAdded = function (e)
{
    /*if(AGORA.realtimeAddedComment >= AGORA.maxRealtimeMessage)
        $("#agora_chat_container").children().first().remove();*/

    $("#agora_chat_container").scrollTop($("#agora_chat_container").prop("scrollHeight"));

    var elem = $("#agora_datalet_placeholder_" + e.post_id);
    var parent_children = elem.parent().children()[0];
    $(parent_children).emoticonize();

    if(e.component !== "") {
        elem.addClass("agora_fullsize_datalet " + e.component);
        $("#agora_preview_button").hide();
        ODE.reset();
    }
};

