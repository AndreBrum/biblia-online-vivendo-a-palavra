jQuery(document).ready(function($) {
	
    $(".increase").click(function(){

            var size = $(".bovp_text").css("font-size");
            var newSize = parseInt(size.substr(0,2));

            if(newSize <= 20){
                var newSize = newSize + 2;
                $(".bovp_text").css("font-size", newSize);
                var d = new Date();
                d.setTime(d.getTime() + (365*24*60*60*1000));
                var cookie = "bovpcurrentsize=" + newSize + "; expires=" + d.toGMTString()+"; path=/";
                document.cookie = cookie;
            }    
    });
    
    $(".decrease").click(function(){
            var size = $(".bovp_text").css("font-size");
            var newSize = parseInt(size.substr(0,2));
            if(newSize >= 14){
                var newSize = newSize - 2;
                $(".bovp_text").css("font-size", newSize);
                var d = new Date();
                d.setTime(d.getTime() + (365*24*60*60*1000));
                var cookie = "bovpcurrentsize=" + newSize + "; expires=" + d.toGMTString()+"; path=/";
                document.cookie = cookie;
            }
    });
  
    //SUBMIT SEARCH
    $('.furl_submit').bind('submit', function(e) {
       e.preventDefault();
       var f = $(this);
       var search = $('#bovp_search_text').attr('value').replace(/\ /g, '-');
       $('#bovp_search_text').removeAttr( "name" );
       f.attr('action', f.attr('action') + search);
       f[0].submit();
    });

    //SUBMIT INDEX FURL
    $('#bovp_furl_index_select').change(function(e) {
        e.preventDefault();

       var f = $('#bovp_book_select');

       var page = $('#bovp_page').attr('value');
       var bovp_type = $('#bovp_type').attr('value');
       var bovp_book = $('#bovp_furl_index_select').attr('value');

       f.attr('action', f.attr('action') + page + "/" + bovp_type + "/" + bovp_book);

       f[0].submit();

    });

    //SUBMIT INDEX DEFAULT URL
    $('#bovp_index_select').change(function(e) {

       e.preventDefault();
       var f = $('#bovp_book_select');
       f[0].submit();

    });

    //OPEN-CLOSE CHAPTER NAVIGATOR
    $( ".current_cap" ).click(function(e) {
        e.preventDefault();
        if( $(this).next().hasClass( "closed_caps" ) ) { 
            $(this).find('span.down_arrow').removeClass( "down_arrow" ).addClass( "up_arrow" );
            $(this).next().removeClass( "closed_caps" ).addClass( "opened_caps" );
            $(this).next().show("slow");        
        } else { 
            $(this).find('span.up_arrow').removeClass( "up_arrow" ).addClass( "down_arrow" );
            $(this).next().removeClass( "opened_caps" ).addClass( "closed_caps" );
            $(this).next().hide("slow");
        }
    });

     $('.bovp_popup').click(function(e) { // WINDOW SHARE POPUP
        e.preventDefault();
        window.open($(this).attr("href"), "popupWindow", "width=600,height=600,scrollbars=no");
    });


    /*
    $('#').click(function(e) {

        if (parent.frames.length > 0) {

          var link = $(this).attr("href") + "/ext";

          $(this).attr("href", link);


        }

        

    });

    */



});