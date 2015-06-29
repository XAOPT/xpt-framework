//(function($){$.fn.extend({leanModal:function(options){var defaults={top:100,overlay:0.5,closeButton:null};var overlay=$("<div id='lean_overlay'></div>");$("body").append(overlay);options=$.extend(defaults,options);return this.each(function(){var o=options;$(this).click(function(e){var modal_id=$(this).attr("href");$("#lean_overlay").click(function(){close_modal(modal_id)});$(o.closeButton).click(function(){close_modal(modal_id)});var modal_height=$(modal_id).outerHeight();var modal_width=$(modal_id).outerWidth();
//$("#lean_overlay").css({"display":"block",opacity:0});$("#lean_overlay").fadeTo(200,o.overlay);$(modal_id).css({"display":"block","position":"fixed","opacity":0,"z-index":11000,"left":50+"%","margin-left":-(modal_width/2)+"px","top":o.top+"px"});$(modal_id).fadeTo(200,1);e.preventDefault()})});function close_modal(modal_id){$("#lean_overlay").fadeOut(200);$(modal_id).css({"display":"none"})}}})})(jQuery);


(function ($) {
    $.fn.extend({
        leanModal: function (options) {
            var defaults = {
                top: 100,
                overlay: 0.5,
                closeButton: null
            };
            var overlay = $("<div id='lean_overlay'></div>");
            $("body").append(overlay);
            options = $.extend(defaults, options);
            return this.each(function () {
                var o = options;
                $(this).click(function (e) {
                	var modal_id = $(this).attr("href");
                	if (o.onShow && typeof o.onShow === "function") {
                		o.onShow.apply(this, [modal_id]);
                	}
                    $("#lean_overlay").click(function () {
                        close_modal(modal_id)
                    });
                    $(o.closeButton).click(function () {
                        close_modal(modal_id)
                    });
                    var modal_height = $(modal_id).outerHeight();
                    var modal_width = $(modal_id).outerWidth();
                    $("#lean_overlay").css({
                        "display": "block",
                        opacity: 0
                    });
                    $("#lean_overlay").fadeTo(200, o.overlay);
                    $(modal_id).css({
                        "display": "block",
                        "position": "fixed",
                        "opacity": 0,
                        "z-index": 11000,
                        "left": 50 + "%",
                        "margin-left": -(modal_width / 2) + "px",
                        "top": o.top + "px"
                    });
                    $(modal_id).fadeTo(200, 1);
                    e.preventDefault()
                })
            });

            function close_modal(modal_id) {
                $("#lean_overlay").fadeOut(200);
                $(modal_id).css({
                    "display": "none"
                });
                if (options.onHide && typeof options.onHide === "function") {
                	options.onHide(modal_id);
                }
            }
        }
    })
})(jQuery);

function insertBB(ta, open, close, value)
{
	var _open   	=  open || "";
	var _close  	=  close || "";
	var start   	=  end = 0;
	var obj		    =  $("#"+ta)[0]; 
	var value       =  value || '';
  	
	
	
		var set_caret = function(pos)
		{
			if (obj.createTextRange) 
			{
				var caret = obj.createTextRange();
				caret.collapse();
				caret.moveStart("character", pos);
				caret.select();
			}
			else if(window.getSelection) 
			{
				obj.setSelectionRange(pos, pos);
				obj.focus();
				
			}	
		}
	 	  
		if (document.getSelection || window.getSelection)  
		{
			 start = obj.selectionStart;
		     end = obj.selectionEnd;
		}  
						   
		if (start === end) 
		{
			var str = obj.value;
			obj.value = str.substring(0, start) + _open + value + _close + str.substr(start);
			
			if(value)
				var end = value.length + _close.length;
			else
				var end = 0;
			
			var pos = start + _open.length + end;
		}
		else if (start < end) 
		{
			var str        	   = obj.value;
			var sel_text       = (value)? value: str.substring(start, end);
			
			var start_space_ch     = sel_text.match(/(^\s|\n)+/);
			var end_space_ch       = sel_text.match(/(\s|\n$)+/);
			
			var start_space  	   = (start_space_ch) ? start_space_ch[0] : '';
			var end_space    	   = (end_space_ch)   ? end_space_ch[0]   : '';
						
			sel_text = sel_text.replace(/(^(\s|\n)*)|((\s|\n)$)*/g, "");
			 														
			obj.value = str.substring(0, start) + start_space + _open + sel_text + _close + end_space + str.substr(end);
			
			var pos = start + _open.length + sel_text.length + _close.length + start_space.length + end_space.length;
		}
		
		if(pos)
			set_caret(pos);
	  
	 
}

function bbPopup(obj, open, close)
{
	var result = prompt("Укажите адрес изображения");
	
	if(result === '')
	{
		alert("Введите адрес изображения");
		bbPopup(obj, open, close);
	}
	else if(result !== null)
		insertBB(obj, open, close, result);
	
}

function bbPopupUrl(obj)
{
	var result = prompt("Укажите адрес ссылки", "http://");
	
	if(result === '')
	{
		alert("Введите адрес ссылки");
		bbPopupUrl(obj);
	}
	else if(!result.match(/(htt(p|ps)|ftp|localhost(\:\/\/))*(www\.)*[a-zа-яё\d\-\_]+(\.)+[a-zа-яё\d]{1,10}(.)*/i))
	{
		alert("Неправильный формат ссылки");
		bbPopupUrl(obj);
	}	
	else if(result !== null)
		bbPopupUrl2(obj, result);
	
}
function bbPopupUrl2(obj, link)
{
	var result = prompt("Введите текст ссылки", link);
	
	if(result === '')
	{
		alert("Введите текст ссылки");
		bbPopupUrl2(obj, link);
	}
	else if(result !== null)
		insertBB(obj, "[url="+link+"]", "[/url]", result);
	
}
