function discussionJs() {};

discussionJs.prototype = (function(){

    var _elem;
    var _entityId;
    var _endpoint;
    var _message;
    var _agoraCommentJS;
    var _stringHandler;
    var _preview;
    var _lock;
    var _current_mention_position;

    var _agora_datalet_preview;

    var init = function(elem, entityId, endpoint) {
        _elem = elem;
        _entityId = entityId;
        _endpoint = endpoint;
        _stringHandler = null;

        _lock = false;

        _agoraCommentJS = new agoraCommentJS();
        _elem.keydown(keydown_handler);

        _agora_datalet_preview = $("#agora_datalet_preview");
    };

    var set_string_handler = function(stringHandler){
        _stringHandler = stringHandler;
    };

    var keydown_handler = function (e) {

        if(_lock) return;

        var key = e.which || e.keyCode;

        if (key === 13 && !e.shiftKey ) { // 13 is enter
            e.preventDefault();
            if(_elem.val() === "") return false;
            handle_message(_elem.val());
        }

        // if ((key === 192 && e.ctrlKey) || e.key == '@') { // 192 is ò
        if (e.key === '@') {
            var coordinates = getCaretCoordinates(_elem[0], _elem[0].selectionEnd);
            _current_mention_position = _elem.prop("selectionStart");
            _elem.on("keyup", handle_mention);

            _suggested_friends.css({
                top :  _elem.parent().position().top - _suggested_friends.outerHeight() + coordinates.top + 12,
                left : _elem.parent().position().left + coordinates.left + 52,
                position:'absolute'
            });
            _suggested_friends.show();
            _suggested_friends_table_tr.on("click", handle_mention_selection);
        }
    };


    var submit = function () {
        if(_elem.val() === "" || _lock) return false;
        handle_message(_elem.val());
        return true;
    };

    var handle_message = function(message) {

        _lock = true;
        _message = message;

        var send_data = {
            comment: _message,
            preview: _preview,
            entityId: _entityId,
            datalet: ODE.dataletParameters,
            plugin: 'cocreation',
            username: SPODDISCUSSION.username,
            user_url: SPODDISCUSSION.user_url,
            user_avatar_src: SPODDISCUSSION.user_avatar_src,
            user_avatar_css: SPODDISCUSSION.user_avatar_css,
            user_avatar_initial: SPODDISCUSSION.user_avatar_initial
        };

        $.ajax({
            type: 'POST',
            url : _endpoint,
            data: send_data,
            dataType : 'JSON',
            success : on_request_success,
            error: on_request_error
        });
    };

    var on_request_success = function(raw_data){
        try
        {
            if(raw_data.result === "ok")
            {
                var target           = $("#agora_chat_container");
                var snippet_url      = SPODDISCUSSION.static_resource_url + 'JSSnippet/comment.tpl';
                var snippet_data     = [raw_data.post_id, SPODDISCUSSION.user_avatar_css, SPODDISCUSSION.username, SPODDISCUSSION.user_url, SPODDISCUSSION.user_avatar_src, SPODDISCUSSION.user_avatar_initial, _stringHandler(_message), raw_data.post_id, SPODDISCUSSION.username, OW.getLanguageText('spodagora', 'c_just_now')];
                var datalet          = ODE.dataletParameters;
                var post_id          = raw_data.post_id;

                append_comment(snippet_url, snippet_data, datalet, post_id, target).then(function(){
                    _agora_datalet_preview.hide()
                });

            }else{
                console.log("Error on comment add");
            }

            _elem.val("");
            //Simulate canc in order to shrink textarea
            _elem.trigger({type:"keyup", ctrlKey:false, which:46});
            _lock = false;

        } catch (e){
            console.log("Error on on_request_success");
        }
    };

    var add_rt_comment = function(snippet_url, snippet_data, datalet, post_id, target){
        append_comment(snippet_url, snippet_data, datalet, post_id, target);
    };

    var append_comment = function(snippet_template, snippet_data, datalet, post_id, target)
    {
        return _agoraCommentJS.getSnippet(snippet_template).then(function(snippet){

            return new Promise(function(res, rej) {

                $(target).append(fill_snippet(snippet, snippet_data));

                if (datalet.component !== "") {
                    ODE.loadDatalet(datalet.component,
                        JSON.parse(datalet.params),
                        JSON.parse("[" + datalet.fields + "]"),
                        datalet.data,
                        "agora_datalet_placeholder_" + post_id);
                }

                $(window).trigger({
                    type: "comment_added",
                    post_id: post_id,
                    component: datalet.component
                });

                res();
            });
        });
    };

    var on_request_error = function( XMLHttpRequest, textStatus, errorThrown ){
        OW.error(textStatus);
    };

    var fill_snippet = function(snippet, snippet_data)
    {
        var re = /{[0-9]+}/g;
        var index = 0;

        var k = snippet.replace(re, function (match, tag, string) {
            return snippet_data[index++];
        });

        return k;
    };

    var debounce = function(f, debounce)
    {
        var timeout;
        return function()
        {
            //var args = arguments;
            if(timeout)
                clearTimeout(timeout);
            timeout = setTimeout(f.bind(null, arguments), debounce)
        }
    };

    var splice = function(str, idx, rem, str_add) {
        return str.slice(0, idx) + str_add + str.slice(idx + Math.abs(rem));
    };


    // PUBLIC METHOD
    return {
        construct : discussionJs,

        init : function (elem, entityId, endpoint) {
            init(elem, entityId, endpoint);
        },

        set_string_handler : function(stringHandler){
            set_string_handler(stringHandler);
        },

        submit : function () {
            return submit();
        },

        add_rt_comment : function (target, snippet_url, snippet_data, post_id, datalet) {
            add_rt_comment(target, snippet_url, snippet_data, post_id, datalet);
        }
    };

})();

function agoraCommentJS(){
    this._snippetCache = {};
}

agoraCommentJS.prototype = (function () {
    return {
        construct: agoraCommentJS,

        getSnippet : function (snippet_url) {

            var cache = this._snippetCache;

            return new Promise(function(res, rej){
                if(!cache[snippet_url]) {
                    $.get(snippet_url, function (data) {
                        cache[snippet_url] = data;
                        res(cache[snippet_url]);
                    });
                }else{
                    res(cache[snippet_url]);
                }
            });
        }
    }
})();