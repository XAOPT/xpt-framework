/*!
 * jQuery Cookie Plugin v1.3.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as anonymous module.
        define(['jquery'], factory);
    } else {
        // Browser globals.
        factory(jQuery);
    }
}(function ($) {

    var pluses = /\+/g;

    function raw(s) {
        return s;
    }

    function decoded(s) {
        return decodeURIComponent(s.replace(pluses, ' '));
    }

    function converted(s) {
        if (s.indexOf('"') === 0) {
            // This is a quoted cookie as according to RFC2068, unescape
            s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        }
        try {
            return config.json ? JSON.parse(s) : s;
        } catch(er) {}
    }

    var config = $.cookie = function (key, value, options) {

        // write
        if (value !== undefined) {
            options = $.extend({}, config.defaults, options);

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setDate(t.getDate() + days);
            }

            value = config.json ? JSON.stringify(value) : String(value);

            return (document.cookie = [
                config.raw ? key : encodeURIComponent(key),
                '=',
                config.raw ? value : encodeURIComponent(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path    ? '; path=' + options.path : '',
                options.domain  ? '; domain=' + options.domain : '',
                options.secure  ? '; secure' : ''
            ].join(''));
        }

        // read
        var decode = config.raw ? raw : decoded;
        var cookies = document.cookie.split('; ');
        var result = key ? undefined : {};
        for (var i = 0, l = cookies.length; i < l; i++) {
            var parts = cookies[i].split('=');
            var name = decode(parts.shift());
            var cookie = decode(parts.join('='));

            if (key && key === name) {
                result = converted(cookie);
                break;
            }

            if (!key) {
                result[name] = converted(cookie);
            }
        }

        return result;
    };

    config.defaults = {};

    $.removeCookie = function (key, options) {
        if ($.cookie(key) !== undefined) {
            // Must not alter options, thus extending a fresh object...
            $.cookie(key, '', $.extend({}, options, { expires: -1 }));
            return true;
        }
        return false;
    };

}));


/*!
    jQuery Autosize v1.16.9
    (c) 2013 Jack Moore - jacklmoore.com
    updated: 2013-05-20
    license: http://www.opensource.org/licenses/mit-license.php
*/
(function(e){var t,o,n={className:"autosizejs",append:"",callback:!1},i="hidden",s="border-box",a="lineHeight",l='<textarea tabindex="-1" style="position:absolute; top:-999px; left:0; right:auto; bottom:auto; border:0; -moz-box-sizing:content-box; -webkit-box-sizing:content-box; box-sizing:content-box; word-wrap:break-word; height:0 !important; min-height:0 !important; overflow:hidden;"/>',r=["fontFamily","fontSize","fontWeight","fontStyle","letterSpacing","textTransform","wordSpacing","textIndent"],c="oninput",h="onpropertychange",p=e(l).data("autosize",!0)[0];p.style.lineHeight="99px","99px"===e(p).css(a)&&r.push(a),p.style.lineHeight="",e.fn.autosize=function(a){return a=e.extend({},n,a||{}),p.parentNode!==document.body&&(e(document.body).append(p),p.value="\n\n\n",p.scrollTop=9e4,t=p.scrollHeight===p.scrollTop+p.clientHeight),this.each(function(){function n(){o=b,p.className=a.className,e.each(r,function(e,t){p.style[t]=f.css(t)})}function l(){var e,s,l;if(o!==b&&n(),!d){d=!0,p.value=b.value+a.append,p.style.overflowY=b.style.overflowY,l=parseInt(b.style.height,10),p.style.width=Math.max(f.width(),0)+"px",t?e=p.scrollHeight:(p.scrollTop=0,p.scrollTop=9e4,e=p.scrollTop);var r=parseInt(f.css("maxHeight"),10);r=r&&r>0?r:9e4,e>r?(e=r,s="scroll"):u>e&&(e=u),e+=x,b.style.overflowY=s||i,l!==e&&(b.style.height=e+"px",w&&a.callback.call(b,b)),setTimeout(function(){d=!1},1)}}var u,d,g,b=this,f=e(b),x=0,w=e.isFunction(a.callback);f.data("autosize")||((f.css("box-sizing")===s||f.css("-moz-box-sizing")===s||f.css("-webkit-box-sizing")===s)&&(x=f.outerHeight()-f.height()),u=Math.max(parseInt(f.css("minHeight"),10)-x,f.height()),g="none"===f.css("resize")||"vertical"===f.css("resize")?"none":"horizontal",f.css({overflow:i,overflowY:i,wordWrap:"break-word",resize:g}).data("autosize",!0),h in b?c in b?b[c]=b.onkeyup=l:b[h]=l:b[c]=l,e(window).on("resize",function(){d=!1,l()}),f.on("autosize",function(){d=!1,l()}),l())})}})(window.jQuery||window.Zepto);


/*!
* TableSorter 2.0 - Client-side table sorting with ease!
* Copyright (c) 2007 Christian Bach
*/

(function($){$.extend({tablesorter:new function(){function benchmark(e,t){log(e+","+((new Date).getTime()-t.getTime())+"ms")}function log(e){if(typeof console!="undefined"&&typeof console.debug!="undefined"){console.log(e)}else{alert(e)}}function buildParserCache(e,t){if(e.config.debug){var n=""}if(e.tBodies.length==0)return;var r=e.tBodies[0].rows;if(r[0]){var i=[],s=r[0].cells,o=s.length;for(var u=0;u<o;u++){var a=false;if($.metadata&&$(t[u]).metadata()&&$(t[u]).metadata().sorter){a=getParserById($(t[u]).metadata().sorter)}else if(e.config.headers[u]&&e.config.headers[u].sorter){a=getParserById(e.config.headers[u].sorter)}if(!a){a=detectParserForColumn(e,r,-1,u)}if(e.config.debug){n+="column:"+u+" parser:"+a.id+"\n"}i.push(a)}}if(e.config.debug){log(n)}return i}function detectParserForColumn(e,t,n,r){var i=parsers.length,s=false,o=false,u=true;while(o==""&&u){n++;if(t[n]){s=getNodeFromRowAndCellIndex(t,n,r);o=trimAndGetNodeText(e.config,s);if(e.config.debug){log("Checking if value was empty on row:"+n)}}else{u=false}}for(var a=1;a<i;a++){if(parsers[a].is(o,e,s)){return parsers[a]}}return parsers[0]}function getNodeFromRowAndCellIndex(e,t,n){return e[t].cells[n]}function trimAndGetNodeText(e,t){return $.trim(getElementText(e,t))}function getParserById(e){var t=parsers.length;for(var n=0;n<t;n++){if(parsers[n].id.toLowerCase()==e.toLowerCase()){return parsers[n]}}return false}function buildCache(e){if(e.config.debug){var t=new Date}var n=e.tBodies[0]&&e.tBodies[0].rows.length||0,r=e.tBodies[0].rows[0]&&e.tBodies[0].rows[0].cells.length||0,i=e.config.parsers,s={row:[],normalized:[]};for(var o=0;o<n;++o){var u=$(e.tBodies[0].rows[o]),a=[];if(u.hasClass(e.config.cssChildRow)){s.row[s.row.length-1]=s.row[s.row.length-1].add(u);continue}s.row.push(u);for(var f=0;f<r;++f){a.push(i[f].format(getElementText(e.config,u[0].cells[f]),e,u[0].cells[f]))}a.push(s.normalized.length);s.normalized.push(a);a=null}if(e.config.debug){benchmark("Building cache for "+n+" rows:",t)}return s}function getElementText(e,t){var n="";if(!t)return"";if(!e.supportsTextContent)e.supportsTextContent=t.textContent||false;if(e.textExtraction=="simple"){if(e.supportsTextContent){n=t.textContent}else{if(t.childNodes[0]&&t.childNodes[0].hasChildNodes()){n=t.childNodes[0].innerHTML}else{n=t.innerHTML}}}else{if(typeof e.textExtraction=="function"){n=e.textExtraction(t)}else{n=$(t).text()}}return n}function appendToTable(e,t){if(e.config.debug){var n=new Date}var r=t,i=r.row,s=r.normalized,o=s.length,u=s[0].length-1,a=$(e.tBodies[0]),f=[];for(var l=0;l<o;l++){var c=s[l][u];f.push(i[c]);if(!e.config.appender){var h=i[c].length;for(var p=0;p<h;p++){a[0].appendChild(i[c][p])}}}if(e.config.appender){e.config.appender(e,f)}f=null;if(e.config.debug){benchmark("Rebuilt table:",n)}applyWidget(e);setTimeout(function(){$(e).trigger("sortEnd")},0)}function buildHeaders(e){if(e.config.debug){var t=new Date}var n=$.metadata?true:false;var r=computeTableHeaderCellIndexes(e);$tableHeaders=$(e.config.selectorHeaders,e).each(function(t){this.column=r[this.parentNode.rowIndex+"-"+this.cellIndex];this.order=formatSortingOrder(e.config.sortInitialOrder);this.count=this.order;if(checkHeaderMetadata(this)||checkHeaderOptions(e,t))this.sortDisabled=true;if(checkHeaderOptionsSortingLocked(e,t))this.order=this.lockedOrder=checkHeaderOptionsSortingLocked(e,t);if(!this.sortDisabled){var n=$(this).addClass(e.config.cssHeader);if(e.config.onRenderHeader)e.config.onRenderHeader.apply(n)}e.config.headerList[t]=this});if(e.config.debug){benchmark("Built headers:",t);log($tableHeaders)}return $tableHeaders}function computeTableHeaderCellIndexes(e){var t=[];var n={};var r=e.getElementsByTagName("THEAD")[0];var i=r.getElementsByTagName("TR");for(var s=0;s<i.length;s++){var o=i[s].cells;for(var u=0;u<o.length;u++){var a=o[u];var f=a.parentNode.rowIndex;var l=f+"-"+a.cellIndex;var c=a.rowSpan||1;var h=a.colSpan||1;var p;if(typeof t[f]=="undefined"){t[f]=[]}for(var d=0;d<t[f].length+1;d++){if(typeof t[f][d]=="undefined"){p=d;break}}n[l]=p;for(var d=f;d<f+c;d++){if(typeof t[d]=="undefined"){t[d]=[]}var v=t[d];for(var m=p;m<p+h;m++){v[m]="x"}}}}return n}function checkCellColSpan(e,t,n){var r=[],i=e.tHead.rows,s=i[n].cells;for(var o=0;o<s.length;o++){var u=s[o];if(u.colSpan>1){r=r.concat(checkCellColSpan(e,headerArr,n++))}else{if(e.tHead.length==1||u.rowSpan>1||!i[n+1]){r.push(u)}}}return r}function checkHeaderMetadata(e){if($.metadata&&$(e).metadata().sorter===false){return true}return false}function checkHeaderOptions(e,t){if(e.config.headers[t]&&e.config.headers[t].sorter===false){return true}return false}function checkHeaderOptionsSortingLocked(e,t){if(e.config.headers[t]&&e.config.headers[t].lockedOrder)return e.config.headers[t].lockedOrder;return false}function applyWidget(e){var t=e.config.widgets;var n=t.length;for(var r=0;r<n;r++){getWidgetById(t[r]).format(e)}}function getWidgetById(e){var t=widgets.length;for(var n=0;n<t;n++){if(widgets[n].id.toLowerCase()==e.toLowerCase()){return widgets[n]}}}function formatSortingOrder(e){if(typeof e!="Number"){return e.toLowerCase()=="desc"?1:0}else{return e==1?1:0}}function isValueInArray(e,t){var n=t.length;for(var r=0;r<n;r++){if(t[r][0]==e){return true}}return false}function setHeadersCss(e,t,n,r,i){t.find("span").remove();t.removeClass(r[0]).removeClass(r[1]);var s=[];t.each(function(e){if(!this.sortDisabled){s[this.column]=$(this)}});var o=n.length;for(var u=0;u<o;u++){s[n[u][0]].addClass(r[n[u][1]]);if(r[n[u][1]]==i.cssAsc)s[n[u][0]].append("<span class='"+i.cssAscAppend+"'></span>");if(r[n[u][1]]==i.cssDesc)s[n[u][0]].append("<span class='"+i.cssDescAppend+"'></span>")}}function fixColumnWidth(e,t){var n=e.config;if(n.widthFixed){var r=$("<colgroup>");$("tr:first td",e.tBodies[0]).each(function(){r.append($("<col>").css("width",$(this).width()))});$(e).prepend(r)}}function updateHeaderSortCount(e,t){var n=e.config,r=t.length;for(var i=0;i<r;i++){var s=t[i],o=n.headerList[s[0]];o.count=s[1];o.count++}}function multisort(table,sortList,cache){if(table.config.debug){var sortTime=new Date}var dynamicExp="var sortWrapper = function(a,b) {",l=sortList.length;for(var i=0;i<l;i++){var c=sortList[i][0];var order=sortList[i][1];var s=table.config.parsers[c].type=="text"?order==0?makeSortFunction("text","asc",c):makeSortFunction("text","desc",c):order==0?makeSortFunction("numeric","asc",c):makeSortFunction("numeric","desc",c);var e="e"+i;dynamicExp+="var "+e+" = "+s;dynamicExp+="if("+e+") { return "+e+"; } ";dynamicExp+="else { "}var orgOrderCol=cache.normalized[0].length-1;dynamicExp+="return a["+orgOrderCol+"]-b["+orgOrderCol+"];";for(var i=0;i<l;i++){dynamicExp+="}; "}dynamicExp+="return 0; ";dynamicExp+="}; ";if(table.config.debug){benchmark("Evaling expression:"+dynamicExp,new Date)}eval(dynamicExp);cache.normalized.sort(sortWrapper);if(table.config.debug){benchmark("Sorting on "+sortList.toString()+" and dir "+order+" time:",sortTime)}return cache}function makeSortFunction(e,t,n){var r="a["+n+"]",i="b["+n+"]";if(e=="text"&&t=="asc"){return"("+r+" == "+i+" ? 0 : ("+r+" === null ? Number.POSITIVE_INFINITY : ("+i+" === null ? Number.NEGATIVE_INFINITY : ("+r+" < "+i+") ? -1 : 1 )));"}else if(e=="text"&&t=="desc"){return"("+r+" == "+i+" ? 0 : ("+r+" === null ? Number.POSITIVE_INFINITY : ("+i+" === null ? Number.NEGATIVE_INFINITY : ("+i+" < "+r+") ? -1 : 1 )));"}else if(e=="numeric"&&t=="asc"){return"("+r+" === null && "+i+" === null) ? 0 :("+r+" === null ? Number.POSITIVE_INFINITY : ("+i+" === null ? Number.NEGATIVE_INFINITY : "+r+" - "+i+"));"}else if(e=="numeric"&&t=="desc"){return"("+r+" === null && "+i+" === null) ? 0 :("+r+" === null ? Number.POSITIVE_INFINITY : ("+i+" === null ? Number.NEGATIVE_INFINITY : "+i+" - "+r+"));"}}function makeSortText(e){return"((a["+e+"] < b["+e+"]) ? -1 : ((a["+e+"] > b["+e+"]) ? 1 : 0));"}function makeSortTextDesc(e){return"((b["+e+"] < a["+e+"]) ? -1 : ((b["+e+"] > a["+e+"]) ? 1 : 0));"}function makeSortNumeric(e){return"a["+e+"]-b["+e+"];"}function makeSortNumericDesc(e){return"b["+e+"]-a["+e+"];"}function sortText(e,t){if(table.config.sortLocaleCompare)return e.localeCompare(t);return e<t?-1:e>t?1:0}function sortTextDesc(e,t){if(table.config.sortLocaleCompare)return t.localeCompare(e);return t<e?-1:t>e?1:0}function sortNumeric(e,t){return e-t}function sortNumericDesc(e,t){return t-e}function getCachedSortType(e,t){return e[t].type}var parsers=[],widgets=[];this.defaults={cssHeader:"header",cssAsc:"headerSortUp",cssDesc:"headerSortDown",cssAscAppend:"glyphicon glyphicon-arrow-up",cssDescAppend:"glyphicon glyphicon-arrow-down",cssChildRow:"expand-child",sortInitialOrder:"asc",sortMultiSortKey:"shiftKey",sortForce:null,sortAppend:null,sortLocaleCompare:true,textExtraction:"simple",parsers:{},widgets:[],widgetZebra:{css:["even","odd"]},headers:{},widthFixed:false,cancelSelection:true,sortList:[],headerList:[],dateFormat:"us",decimal:"/.|,/g",onRenderHeader:null,selectorHeaders:"thead th",debug:false};this.benchmark=benchmark;this.construct=function(e){return this.each(function(){if(!this.tHead||!this.tBodies)return;var t,n,r,i,s,o=0,u;this.config={};s=$.extend(this.config,$.tablesorter.defaults,e);t=$(this);$.data(this,"tablesorter",s);r=buildHeaders(this);this.config.parsers=buildParserCache(this,r);i=buildCache(this);var a=[s.cssDesc,s.cssAsc];fixColumnWidth(this);r.click(function(e){var n=t[0].tBodies[0]&&t[0].tBodies[0].rows.length||0;if(!this.sortDisabled&&n>0){t.trigger("sortStart");var o=$(this);var u=this.column;this.order=this.count++%2;if(this.lockedOrder)this.order=this.lockedOrder;if(!e[s.sortMultiSortKey]){s.sortList=[];if(s.sortForce!=null){var f=s.sortForce;for(var l=0;l<f.length;l++){if(f[l][0]!=u){s.sortList.push(f[l])}}}s.sortList.push([u,this.order])}else{if(isValueInArray(u,s.sortList)){for(var l=0;l<s.sortList.length;l++){var c=s.sortList[l],h=s.headerList[c[0]];if(c[0]==u){h.count=c[1];h.count++;c[1]=h.count%2}}}else{s.sortList.push([u,this.order])}}setTimeout(function(){setHeadersCss(t[0],r,s.sortList,a,s);appendToTable(t[0],multisort(t[0],s.sortList,i))},1);return false}}).mousedown(function(){if(s.cancelSelection){this.onselectstart=function(){return false};return false}});t.bind("update",function(){var e=this;setTimeout(function(){e.config.parsers=buildParserCache(e,r);i=buildCache(e)},1)}).bind("updateCell",function(e,t){var n=this.config;var r=[t.parentNode.rowIndex-1,t.cellIndex];i.normalized[r[0]][r[1]]=n.parsers[r[1]].format(getElementText(n,t),t)}).bind("sorton",function(e,t){$(this).trigger("sortStart");s.sortList=t;var n=s.sortList;updateHeaderSortCount(this,n);setHeadersCss(this,r,n,a,s);appendToTable(this,multisort(this,n,i))}).bind("appendCache",function(){appendToTable(this,i)}).bind("applyWidgetId",function(e,t){getWidgetById(t).format(this)}).bind("applyWidgets",function(){applyWidget(this)});if($.metadata&&$(this).metadata()&&$(this).metadata().sortlist){s.sortList=$(this).metadata().sortlist}if(s.sortList.length>0){t.trigger("sorton",[s.sortList])}applyWidget(this)})};this.addParser=function(e){var t=parsers.length,n=true;for(var r=0;r<t;r++){if(parsers[r].id.toLowerCase()==e.id.toLowerCase()){n=false}}if(n){parsers.push(e)}};this.addWidget=function(e){widgets.push(e)};this.formatFloat=function(e){var t=parseFloat(e.replace(",","."));return isNaN(t)?0:t};this.formatInt=function(e){var t=parseInt(e);return isNaN(t)?0:t};this.isDigit=function(e,t){return/^[-+]?\d*$/.test($.trim(e.replace(/[,.']/g,"")))};this.clearTableBody=function(e){if($.browser.msie){function t(){while(this.firstChild)this.removeChild(this.firstChild)}t.apply(e.tBodies[0])}else{e.tBodies[0].innerHTML=""}}}});$.fn.extend({tablesorter:$.tablesorter.construct});var ts=$.tablesorter;ts.addParser({id:"text",is:function(e){return true},format:function(e){return $.trim(e.toLocaleLowerCase())},type:"text"});ts.addParser({id:"digit",is:function(e,t){var n=t.config;return $.tablesorter.isDigit(e,n)},format:function(e){return $.tablesorter.formatFloat(e)},type:"numeric"});ts.addParser({id:"currency",is:function(e){return/^[£$€?.]/.test(e)},format:function(e){return $.tablesorter.formatFloat(e.replace(new RegExp(/[£$€]/g),""))},type:"numeric"});ts.addParser({id:"ipAddress",is:function(e){return/^\d{2,3}[\.]\d{2,3}[\.]\d{2,3}[\.]\d{2,3}$/.test(e)},format:function(e){var t=e.split("."),n="",r=t.length;for(var i=0;i<r;i++){var s=t[i];if(s.length==2){n+="0"+s}else{n+=s}}return $.tablesorter.formatFloat(n)},type:"numeric"});ts.addParser({id:"url",is:function(e){return/^(https?|ftp|file):\/\/$/.test(e)},format:function(e){return jQuery.trim(e.replace(new RegExp(/(https?|ftp|file):\/\//),""))},type:"text"});ts.addParser({id:"isoDate",is:function(e){return/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/.test(e)},format:function(e){return $.tablesorter.formatFloat(e!=""?(new Date(e.replace(new RegExp(/-/g),"/"))).getTime():"0")},type:"numeric"});ts.addParser({id:"percent",is:function(e){return/\%$/.test($.trim(e))},format:function(e){return $.tablesorter.formatFloat(e.replace(new RegExp(/%/g),""))},type:"numeric"});ts.addParser({id:"usLongDate",is:function(e){return e.match(new RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, ([0-9]{4}|'?[0-9]{2}) (([0-2]?[0-9]:[0-5][0-9])|([0-1]?[0-9]:[0-5][0-9]\s(AM|PM)))$/))},format:function(e){return $.tablesorter.formatFloat((new Date(e)).getTime())},type:"numeric"});ts.addParser({id:"shortDate",is:function(e){return/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/.test(e)},format:function(e,t){var n=t.config;e=e.replace(/\-/g,"/");if(n.dateFormat=="us"){e=e.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/,"$3/$1/$2")}else if(n.dateFormat=="uk"){e=e.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/,"$3/$2/$1")}else if(n.dateFormat=="dd/mm/yy"||n.dateFormat=="dd-mm-yy"){e=e.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})/,"$1/$2/$3")}return $.tablesorter.formatFloat((new Date(e)).getTime())},type:"numeric"});ts.addParser({id:"time",is:function(e){return/^(([0-2]?[0-9]:[0-5][0-9])|([0-1]?[0-9]:[0-5][0-9]\s(am|pm)))$/.test(e)},format:function(e){return $.tablesorter.formatFloat((new Date("2000/01/01 "+e)).getTime())},type:"numeric"});ts.addParser({id:"metadata",is:function(e){return false},format:function(e,t,n){var r=t.config,i=!r.parserMetadataName?"sortValue":r.parserMetadataName;return $(n).metadata()[i]},type:"numeric"});ts.addWidget({id:"zebra",format:function(e){if(e.config.debug){var t=new Date}var n,r=-1,i;$("tr:visible",e.tBodies[0]).each(function(t){n=$(this);if(!n.hasClass(e.config.cssChildRow))r++;i=r%2==0;n.removeClass(e.config.widgetZebra.css[i?0:1]).addClass(e.config.widgetZebra.css[i?1:0])});if(e.config.debug){$.tablesorter.benchmark("Applying Zebra widget",t)}}})})(jQuery)

/* ===========================================================
 * bootstrap-tooltip.js v2.3.2
 * http://twitter.github.com/bootstrap/javascript.html#tooltips
 * Inspired by the original jQuery.tipsy by Jason Frame
 * ===========================================================
 * Copyright 2012 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */


!function ($) {

  "use strict"; // jshint ;_;


 /* TOOLTIP PUBLIC CLASS DEFINITION
  * =============================== */

  var Tooltip = function (element, options) {
    this.init('tooltip', element, options)
  }

  Tooltip.prototype = {

    constructor: Tooltip

  , init: function (type, element, options) {
      var eventIn
        , eventOut
        , triggers
        , trigger
        , i

      this.type = type
      this.$element = $(element)
      this.options = this.getOptions(options)
      this.enabled = true

      triggers = this.options.trigger.split(' ')

      for (i = triggers.length; i--;) {
        trigger = triggers[i]
        if (trigger == 'click') {
          this.$element.on('click.' + this.type, this.options.selector, $.proxy(this.toggle, this))
        } else if (trigger != 'manual') {
          eventIn = trigger == 'hover' ? 'mouseenter' : 'focus'
          eventOut = trigger == 'hover' ? 'mouseleave' : 'blur'
          this.$element.on(eventIn + '.' + this.type, this.options.selector, $.proxy(this.enter, this))
          this.$element.on(eventOut + '.' + this.type, this.options.selector, $.proxy(this.leave, this))
        }
      }

      this.options.selector ?
        (this._options = $.extend({}, this.options, { trigger: 'manual', selector: '' })) :
        this.fixTitle()
    }

  , getOptions: function (options) {
      options = $.extend({}, $.fn[this.type].defaults, this.$element.data(), options)

      if (options.delay && typeof options.delay == 'number') {
        options.delay = {
          show: options.delay
        , hide: options.delay
        }
      }

      return options
    }

  , enter: function (e) {
      var defaults = $.fn[this.type].defaults
        , options = {}
        , self

      this._options && $.each(this._options, function (key, value) {
        if (defaults[key] != value) options[key] = value
      }, this)

      self = $(e.currentTarget)[this.type](options).data(this.type)

      if (!self.options.delay || !self.options.delay.show) return self.show()

      clearTimeout(this.timeout)
      self.hoverState = 'in'
      this.timeout = setTimeout(function() {
        if (self.hoverState == 'in') self.show()
      }, self.options.delay.show)
    }

  , leave: function (e) {
      var self = $(e.currentTarget)[this.type](this._options).data(this.type)

      if (this.timeout) clearTimeout(this.timeout)
      if (!self.options.delay || !self.options.delay.hide) return self.hide()

      self.hoverState = 'out'
      this.timeout = setTimeout(function() {
        if (self.hoverState == 'out') self.hide()
      }, self.options.delay.hide)
    }

  , show: function () {
      var $tip
        , pos
        , actualWidth
        , actualHeight
        , placement
        , tp
        , e = $.Event('show')

      if (this.hasContent() && this.enabled) {
        this.$element.trigger(e)
        if (e.isDefaultPrevented()) return
        $tip = this.tip()
        this.setContent()

        if (this.options.animation) {
          $tip.addClass('fade')
        }

        placement = typeof this.options.placement == 'function' ?
          this.options.placement.call(this, $tip[0], this.$element[0]) :
          this.options.placement

        $tip
          .detach()
          .css({ top: 0, left: 0, display: 'block' })

        this.options.container ? $tip.appendTo(this.options.container) : $tip.insertAfter(this.$element)

        pos = this.getPosition()

        actualWidth = $tip[0].offsetWidth
        actualHeight = $tip[0].offsetHeight

        switch (placement) {
          case 'bottom':
            tp = {top: pos.top + pos.height, left: pos.left + pos.width / 2 - actualWidth / 2}
            break
          case 'top':
            tp = {top: pos.top - actualHeight, left: pos.left + pos.width / 2 - actualWidth / 2}
            break
          case 'left':
            tp = {top: pos.top + pos.height / 2 - actualHeight / 2, left: pos.left - actualWidth}
            break
          case 'right':
            tp = {top: pos.top + pos.height / 2 - actualHeight / 2, left: pos.left + pos.width}
            break
        }

        this.applyPlacement(tp, placement)
        this.$element.trigger('shown')
      }
    }

  , applyPlacement: function(offset, placement){
      var $tip = this.tip()
        , width = $tip[0].offsetWidth
        , height = $tip[0].offsetHeight
        , actualWidth
        , actualHeight
        , delta
        , replace

      $tip
        .offset(offset)
        .addClass(placement)
        .addClass('in')

      actualWidth = $tip[0].offsetWidth
      actualHeight = $tip[0].offsetHeight

      if (placement == 'top' && actualHeight != height) {
        offset.top = offset.top + height - actualHeight
        replace = true
      }

      if (placement == 'bottom' || placement == 'top') {
        delta = 0

        if (offset.left < 0){
          delta = offset.left * -2
          offset.left = 0
          $tip.offset(offset)
          actualWidth = $tip[0].offsetWidth
          actualHeight = $tip[0].offsetHeight
        }

        this.replaceArrow(delta - width + actualWidth, actualWidth, 'left')
      } else {
        this.replaceArrow(actualHeight - height, actualHeight, 'top')
      }

      if (replace) $tip.offset(offset)
    }

  , replaceArrow: function(delta, dimension, position){
      this
        .arrow()
        .css(position, delta ? (50 * (1 - delta / dimension) + "%") : '')
    }

  , setContent: function () {
      var $tip = this.tip()
        , title = this.getTitle()

      $tip.find('.tooltip-inner')[this.options.html ? 'html' : 'text'](title)
      $tip.removeClass('fade in top bottom left right')
    }

  , hide: function () {
      var that = this
        , $tip = this.tip()
        , e = $.Event('hide')

      this.$element.trigger(e)
      if (e.isDefaultPrevented()) return

      $tip.removeClass('in')

      function removeWithAnimation() {
        var timeout = setTimeout(function () {
          $tip.off($.support.transition.end).detach()
        }, 500)

        $tip.one($.support.transition.end, function () {
          clearTimeout(timeout)
          $tip.detach()
        })
      }

      $.support.transition && this.$tip.hasClass('fade') ?
        removeWithAnimation() :
        $tip.detach()

      this.$element.trigger('hidden')

      return this
    }

  , fixTitle: function () {
      var $e = this.$element
      if ($e.attr('title') || typeof($e.attr('data-original-title')) != 'string') {
        $e.attr('data-original-title', $e.attr('title') || '').attr('title', '')
      }
    }

  , hasContent: function () {
      return this.getTitle()
    }

  , getPosition: function () {
      var el = this.$element[0]
      return $.extend({}, (typeof el.getBoundingClientRect == 'function') ? el.getBoundingClientRect() : {
        width: el.offsetWidth
      , height: el.offsetHeight
      }, this.$element.offset())
    }

  , getTitle: function () {
      var title
        , $e = this.$element
        , o = this.options

      title = $e.attr('data-original-title')
        || (typeof o.title == 'function' ? o.title.call($e[0]) :  o.title)

      return title
    }

  , tip: function () {
      return this.$tip = this.$tip || $(this.options.template)
    }

  , arrow: function(){
      return this.$arrow = this.$arrow || this.tip().find(".tooltip-arrow")
    }

  , validate: function () {
      if (!this.$element[0].parentNode) {
        this.hide()
        this.$element = null
        this.options = null
      }
    }

  , enable: function () {
      this.enabled = true
    }

  , disable: function () {
      this.enabled = false
    }

  , toggleEnabled: function () {
      this.enabled = !this.enabled
    }

  , toggle: function (e) {
      var self = e ? $(e.currentTarget)[this.type](this._options).data(this.type) : this
      self.tip().hasClass('in') ? self.hide() : self.show()
    }

  , destroy: function () {
      this.hide().$element.off('.' + this.type).removeData(this.type)
    }

  }


 /* TOOLTIP PLUGIN DEFINITION
  * ========================= */

  var old = $.fn.tooltip

  $.fn.tooltip = function ( option ) {
    return this.each(function () {
      var $this = $(this)
        , data = $this.data('tooltip')
        , options = typeof option == 'object' && option
      if (!data) $this.data('tooltip', (data = new Tooltip(this, options)))
      if (typeof option == 'string') data[option]()
    })
  }

  $.fn.tooltip.Constructor = Tooltip

  $.fn.tooltip.defaults = {
    animation: true
  , placement: 'top'
  , selector: false
  , template: '<div class="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
  , trigger: 'hover focus'
  , title: ''
  , delay: 0
  , html: false
  , container: false
  }


 /* TOOLTIP NO CONFLICT
  * =================== */

  $.fn.tooltip.noConflict = function () {
    $.fn.tooltip = old
    return this
  }

}(window.jQuery);



/**
 * TableDnD plug-in for JQuery, allows you to drag and drop table rows
 * You can set up various options to control how the system will work
 * Copyright © Denis Howlett <denish@isocra.com>
 * Licensed like jQuery, see http://docs.jquery.com/License.
 * http://isocra.com/2008/02/table-drag-and-drop-jquery-plugin/
 */
jQuery.tableDnD = {
    /** Keep hold of the current table being dragged */
    currentTable : null,
    /** Keep hold of the current drag object if any */
    dragObject: null,
    /** The current mouse offset */
    mouseOffset: null,
    /** Remember the old value of Y so that we don't do too much processing */
    oldY: 0,

    /** Actually build the structure */
    build: function(options) {
        // Make sure options exists
        options = options || {};
        // Set up the defaults if any

        this.each(function() {
            // Remember the options
            this.tableDnDConfig = {
                onDragStyle: options.onDragStyle,
                onDropStyle: options.onDropStyle,
                // Add in the default class for whileDragging
                onDragClass: options.onDragClass ? options.onDragClass : "tDnD_whileDrag",
                onDrop: options.onDrop,
                onDragStart: options.onDragStart,
                scrollAmount: options.scrollAmount ? options.scrollAmount : 5
            };
            // Now make the rows draggable
            jQuery.tableDnD.makeDraggable(this);
        });

        // Now we need to capture the mouse up and mouse move event
        // We can use bind so that we don't interfere with other event handlers
        jQuery(document)
            .bind('mousemove', jQuery.tableDnD.mousemove)
            .bind('mouseup', jQuery.tableDnD.mouseup);

        // Don't break the chain
        return this;
    },

    /** This function makes all the rows on the table draggable apart from those marked as "NoDrag" */
    makeDraggable: function(table) {
        // Now initialise the rows
        var rows = table.rows; //getElementsByTagName("tr")
        var config = table.tableDnDConfig;
        for (var i=0; i<rows.length; i++) {
            // To make non-draggable rows, add the nodrag class (eg for Category and Header rows)
            // inspired by John Tarr and Famic
            var nodrag = $(rows[i]).hasClass("nodrag");
            if (! nodrag) { //There is no NoDnD attribute on rows I want to drag
                jQuery(rows[i]).mousedown(function(ev) {
                    if (ev.target.tagName == "TD") {
                        jQuery.tableDnD.dragObject = this;
                        jQuery.tableDnD.currentTable = table;
                        jQuery.tableDnD.mouseOffset = jQuery.tableDnD.getMouseOffset(this, ev);
                        if (config.onDragStart) {
                            // Call the onDrop method if there is one
                            config.onDragStart(table, this);
                        }
                        return false;
                    }
                }).css("cursor", "move"); // Store the tableDnD object
            }
        }
    },

    /** Get the mouse coordinates from the event (allowing for browser differences) */
    mouseCoords: function(ev){
        if(ev.pageX || ev.pageY){
            return {x:ev.pageX, y:ev.pageY};
        }
        return {
            x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
            y:ev.clientY + document.body.scrollTop  - document.body.clientTop
        };
    },

    /** Given a target element and a mouse event, get the mouse offset from that element.
        To do this we need the element's position and the mouse position */
    getMouseOffset: function(target, ev) {
        ev = ev || window.event;

        var docPos    = this.getPosition(target);
        var mousePos  = this.mouseCoords(ev);
        return {x:mousePos.x - docPos.x, y:mousePos.y - docPos.y};
    },

    /** Get the position of an element by going up the DOM tree and adding up all the offsets */
    getPosition: function(e){
        var left = 0;
        var top  = 0;
        /** Safari fix -- thanks to Luis Chato for this! */
        if (e.offsetHeight == 0) {
            /** Safari 2 doesn't correctly grab the offsetTop of a table row
            this is detailed here:
            http://jacob.peargrove.com/blog/2006/technical/table-row-offsettop-bug-in-safari/
            the solution is likewise noted there, grab the offset of a table cell in the row - the firstChild.
            note that firefox will return a text node as a first child, so designing a more thorough
            solution may need to take that into account, for now this seems to work in firefox, safari, ie */
            e = e.firstChild; // a table cell
        }

        while (e.offsetParent){
            left += e.offsetLeft;
            top  += e.offsetTop;
            e     = e.offsetParent;
        }

        left += e.offsetLeft;
        top  += e.offsetTop;

        return {x:left, y:top};
    },

    mousemove: function(ev) {
        if (jQuery.tableDnD.dragObject == null) {
            return;
        }

        var dragObj = jQuery(jQuery.tableDnD.dragObject);
        var config = jQuery.tableDnD.currentTable.tableDnDConfig;
        var mousePos = jQuery.tableDnD.mouseCoords(ev);
        var y = mousePos.y - jQuery.tableDnD.mouseOffset.y;
        //auto scroll the window
        var yOffset = window.pageYOffset;
        if (document.all) {
            // Windows version
            //yOffset=document.body.scrollTop;
            if (typeof document.compatMode != 'undefined' &&
                 document.compatMode != 'BackCompat') {
               yOffset = document.documentElement.scrollTop;
            }
            else if (typeof document.body != 'undefined') {
               yOffset=document.body.scrollTop;
            }

        }

        if (mousePos.y-yOffset < config.scrollAmount) {
            window.scrollBy(0, -config.scrollAmount);
        } else {
            var windowHeight = window.innerHeight ? window.innerHeight
                    : document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight;
            if (windowHeight-(mousePos.y-yOffset) < config.scrollAmount) {
                window.scrollBy(0, config.scrollAmount);
            }
        }


        if (y != jQuery.tableDnD.oldY) {
            // work out if we're going up or down...
            var movingDown = y > jQuery.tableDnD.oldY;
            // update the old value
            jQuery.tableDnD.oldY = y;
            // update the style to show we're dragging
            if (config.onDragClass) {
                dragObj.addClass(config.onDragClass);
            } else {
                dragObj.css(config.onDragStyle);
            }
            // If we're over a row then move the dragged row to there so that the user sees the
            // effect dynamically
            var currentRow = jQuery.tableDnD.findDropTargetRow(dragObj, y);
            if (currentRow) {
                // TODO worry about what happens when there are multiple TBODIES
                if (movingDown && jQuery.tableDnD.dragObject != currentRow) {
                    jQuery.tableDnD.dragObject.parentNode.insertBefore(jQuery.tableDnD.dragObject, currentRow.nextSibling);
                } else if (! movingDown && jQuery.tableDnD.dragObject != currentRow) {
                    jQuery.tableDnD.dragObject.parentNode.insertBefore(jQuery.tableDnD.dragObject, currentRow);
                }
            }
        }

        return false;
    },

    /** We're only worried about the y position really, because we can only move rows up and down */
    findDropTargetRow: function(draggedRow, y) {
        var rows = jQuery.tableDnD.currentTable.rows;
        for (var i=0; i<rows.length; i++) {
            var row = rows[i];
            var rowY    = this.getPosition(row).y;
            var rowHeight = parseInt(row.offsetHeight)/2;
            if (row.offsetHeight == 0) {
                rowY = this.getPosition(row.firstChild).y;
                rowHeight = parseInt(row.firstChild.offsetHeight)/2;
            }
            // Because we always have to insert before, we need to offset the height a bit
            if ((y > rowY - rowHeight) && (y < (rowY + rowHeight))) {
                // that's the row we're over
                // If it's the same as the current row, ignore it
                if (row == draggedRow) {return null;}
                var config = jQuery.tableDnD.currentTable.tableDnDConfig;
                if (config.onAllowDrop) {
                    if (config.onAllowDrop(draggedRow, row)) {
                        return row;
                    } else {
                        return null;
                    }
                } else {
                    // If a row has nodrop class, then don't allow dropping (inspired by John Tarr and Famic)
                    var nodrop = $(row).hasClass("nodrop");
                    if (! nodrop) {
                        return row;
                    } else {
                        return null;
                    }
                }
                return row;
            }
        }
        return null;
    },

    mouseup: function(e) {
        if (jQuery.tableDnD.currentTable && jQuery.tableDnD.dragObject) {
            var droppedRow = jQuery.tableDnD.dragObject;
            var config = jQuery.tableDnD.currentTable.tableDnDConfig;
            // If we have a dragObject, then we need to release it,
            // The row will already have been moved to the right place so we just reset stuff
            if (config.onDragClass) {
                jQuery(droppedRow).removeClass(config.onDragClass);
            } else {
                jQuery(droppedRow).css(config.onDropStyle);
            }
            jQuery.tableDnD.dragObject   = null;
            if (config.onDrop) {
                // Call the onDrop method if there is one
                config.onDrop(jQuery.tableDnD.currentTable, droppedRow);
            }
            jQuery.tableDnD.currentTable = null; // let go of the table too
        }
    },

    serialize: function() {
        if (jQuery.tableDnD.currentTable) {
            var result = "";
            var tableId = jQuery.tableDnD.currentTable.id;
            var rows = jQuery.tableDnD.currentTable.rows;
            for (var i=0; i<rows.length; i++) {
                if (result.length > 0) result += "&";
                result += tableId + '[]=' + rows[i].id;
            }
            return result;
        } else {
            return "Error: No Table id set, you need to set an id on your table and every row";
        }
    }
}

jQuery.fn.extend(
    {
        tableDnD : jQuery.tableDnD.build
    }
);