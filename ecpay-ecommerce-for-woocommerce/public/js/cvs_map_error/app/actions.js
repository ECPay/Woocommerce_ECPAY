(function($){

//手機觸控事件
    var touchevent  = function(){$(this).addClass('on-touch');};
    var touchevent2 = function(){$(this).removeClass('on-touch');};
    $(document)
    .on(
        'touchstart mouseenter click',
        'a, div, button, .tabs > li, .lcl-box > dl, .mvc-list li', 
        touchevent
        )
    .on(
        'touchend mouseleave click',
        'a, div, button, .tabs > li, .lcl-box > dl, .mvc-list li', 
        touchevent2
        );

})(jQuery);
