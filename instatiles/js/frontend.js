/*jQuery(document).ready(function(){
    jQuery('#tiles li').wookmark({offset: 2});
});*/


jQuery(document).ready(new function() {
    // Prepare layout options.
    var options = {
        autoResize: true, // This will auto-update the layout when the browser window is resized.
        container: jQuery('#main'), // Optional, used for some extra CSS styling
        offset: 2, // Optional, the distance between grid items
        itemWidth: 210 // Optional, the width of a grid item
    };

    // Get a reference to your grid items.
    var handler = jQuery('#tiles li');

    // Call the layout function.
    handler.wookmark(options);

    // Capture clicks on grid items.
    handler.click(function(){
        // Randomize the height of the clicked item.
        var newHeight = jQuery('img', this).height() + Math.round(Math.random()*300+30);
        jQuery(this).css('height', newHeight+'px');

        // Update the layout.
        handler.wookmark();
    });
});
