(function($) 
{
    $('img').each(function(){
        $(this).prop('src',$(this).prop('delayedsrc'));
    })
})(jQuery);