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
			$(this).css('-ms-user-select', 'none');
			$(this).css('user-select', 'none');
		});
		return this;
		}
	});

	$('div.dw-reactions-button').mouseenter(function(e){
		$(this).addClass('reaction-show');
	});

	$('div.dw-reactions-button').mouseleave(function(e){
		$(this).removeClass('reaction-show');
	});

	$('div.dw-reactions-button').on('taphold',function(e){
		e.preventDefault();
		$(this).addClass('reaction-show');
		$(this).disableSelection();
	});

	$('div.dw-reactions-button').disableSelection();

	$('.dw-reaction').on('click', function(e){
		e.preventDefault();

		var t = $(this), $class = $(this).attr('class'), parent = t.parent(), text = t.find('strong').text(), vote_type = parent.parent().find('.dw-reactions-main-button').attr('data-type');
		res = $class.split(' ');
		type = res[1].split('-');

		$('div.dw-reactions-button').removeClass('reaction-show');

		$.ajax({
			url: dw_reaction.ajax,
			dataType: 'json',
			type: 'POST',
			data: {
				action: 'dw_reaction_save_action',
				nonce: parent.data('nonce'),
				type: type[2],
				post: parent.data('post'),
				vote_type: vote_type
			},
			success: function(data) {
				if ( data.success ) {
					$('.dw-reactions-post-'+parent.data('post')).find('.dw-reactions-count').replaceWith(data.data.html);
					$('.dw-reactions-post-'+parent.data('post')).find('.dw-reactions-main-button').attr('class','dw-reactions-main-button').addClass('dw_reaction_'+type[2]).text(text).attr('data-type', 'unvote');
				}
			}
		});
	});

	$('.dw-reactions-main-button').on('click', function(e) {
		e.preventDefault();

		var t = $(this);
		type = t.attr('data-type');
		text = t.parent().find('.dw-reaction-like strong').text();

		$.ajax({
			url: dw_reaction.ajax,
			dataType: 'json',
			type: 'POST',
			data: {
				action: 'dw_reaction_save_action',
				nonce: t.next().data('nonce'),
				type: 'like',
				post: t.next().data('post'),
				vote_type: type
			},
			success: function(data) {
				if ( data.success ) {
					if ( data.data.type == 'unvoted' ) {
						$('.dw-reactions-post-'+t.next().data('post')).find('.dw-reactions-main-button').attr('class', 'dw-reactions-main-button').text(text);
						$('.dw-reactions-post-'+t.next().data('post')).find('.dw-reactions-main-button').attr('data-type', 'vote');
					} else {
						$('.dw-reactions-post-'+t.next().data('post')).find('.dw-reactions-main-button').addClass('dw_reaction_like');
						$('.dw-reactions-post-'+t.next().data('post')).find('.dw-reactions-main-button').attr('data-type', 'unvote');
					}
					$('.dw-reactions-post-'+t.next().data('post')).find('.dw-reactions-count').replaceWith(data.data.html);
				}
			}
		});
	})
});