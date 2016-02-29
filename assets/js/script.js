jQuery(document).ready(function($){
	$.fn.extend({
	disableSelection: function() {
		this.each(function() {
			this.onselectstart = function() {
			    return false;
			};
			this.unselectable = "on";
			$(this).css('-moz-user-select', 'none');
			$(this).css('-webkit-user-select', 'none');
			$(this).css('-webkit-touch-callout', 'none');
			$(this).css('-ms-user-select', 'none');
			$(this).css('user-select', 'none');
			$(this).css('khtml-user-select','none');
		});
		return this;
		}
	});

	$('div.dw-reactions').mouseenter(function(e){
		$(this).addClass('reaction-show');
	});

	$('div.dw-reactions').mouseleave(function(e){
		$(this).removeClass('reaction-show');
	});

	$('div.dw-reactions').on('taphold',function(e){
		e.preventDefault();
		$(this).addClass('reaction-show');
		$(this).disableSelection();
	});

	$('div.dw-reactions').disableSelection();

	$('.reaction').on('click', function(e){
		e.preventDefault();

		var t = $(this), $class = $(this).attr('class'), parent = t.parent();
		res = $class.split(' ');
		type = res[1].split('-');

		$('div.dw-reactions').removeClass('reaction-show');

		$.ajax({
			url: dw_reaction.ajax,
			dataType: 'json',
			type: 'POST',
			data: {
				action: 'dw_reaction_save_action',
				nonce: parent.data('nonce'),
				type: type[1],
				post: parent.data('post')
			},
			success: function(data) {
				console.log(data);
			}
		});
	})
});