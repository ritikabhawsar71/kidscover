var paths = [];

function hideQVButton(element, ce) {
    $jq(element + ' .btn-quickview-' + ce).hide();
}

function showQVButton(element, ce) {
    $jq(element + ' .btn-quickview-' + ce).show();
}

function closeQVFrame() {
	$jq('div.quickview-container').hide();
    $jq('#quickview-bg-block').hide();
    $jq('.quickview-load-img').hide();
    $jq('div#quickview-content').hide(600);
    $jq('div#quickview-content').html('');
	$jq('a.a-qv-close').hide();
}

function appendQuickViewEvent(element, ce) {
    var clickEvent = "ajaxView('" + paths[ce] + "')";
    $jq(element + ' .btn-quickview-' + ce).attr('onclick', clickEvent);
}

function appendCloseFrameLink() {
    $jq('div#quickview-content').prepend("<a href='javascript:void(0);' class='a-qv-close' onclick='closeQVFrame()'>Close</a>");
}

function appendQuickViewinListScript() {
    initQuickButton('.category-products');
    $jq(document).ajaxComplete(function(){
        initQuickButton('.category-products');
    });
}

function initQuickButton(element) {
    var count = 0;
    var subpath = "";
    $jq(element + ' a.product-image').each(function(path) {
        var rel = $jq(this).attr('rel');
        if(rel == null || rel == '') {
            count++;
            path = $jq(this).attr('href');
            subpath = path.substring(path.lastIndexOf('/') + 1, path.length);
            paths[count] = subpath;
            $jq(this).closest("div.item-inner").children('.images-container').children(".actions" ).children(".add-to-links" )
									.append('<li class="qv-button-container"><a ' +'class="qv-e-button btn-quickview-' + count + '" href="javascript:void(0);"><em '+'class="tooltip">'+'QuickView</em>' + 'Quick View</a></li>');
            appendQuickViewEvent(element, count);
			$jq(this).attr('rel', 'author');
        }
		if(rel == 'author') {
			$jq(this).attr('rel', '');
		}
    });
}

function ajaxView(path) {
	
    $jq('#quickview-bg-block').show();
    $jq('.quickview-load-img').show();
	
    $jq.ajax({
        url     : MA.QuickView.BASE_URL + 'quickview/index/view/path/' + path,
        type    : "get",
        success : function(response) {
            
            
			$jq('a.a-qv-close').show();
            $jq('.quickview-load-img').hide();
            $jq('div#quickview-content').show();
			$jq('div.quickview-container').show();
			$jq('div#quickview-content').html(response);
        }
    });
}

$jq(document).ready(function() {
    if(MA.QuickView.CATEGORY_ENABLE == 'true') {
        $jq('.category-products').append('<script type="text/javascript">appendQuickViewinListScript()</script>');
    }
});




