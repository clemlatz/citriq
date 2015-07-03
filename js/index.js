    user_uid = $('#user_uid').val();
    
    function addLookup(x) {
	x.addClass("loading");
	q = x.val();
	if(q.length < 3) { // si le champs txte est vide
	    $('#addResults').html('<em>Entrez un titre de livre, un auteur, un &#233;diteur ou un ISBN.</em>'); // on cache les suggestions
	    x.removeClass("loading");
	} else { // sinon
	    $.get("/x/import", {q: ""+q+""}, function(data){ // on envoit la valeur du champ texte dans la variable post queryString au fichier ajax.php
		    if(data.length > 0) {
			    $('#addResults').html(data); // et on remplit la liste des données
		    } else {
			    $('#addResults').html('<em>Entrez un titre de livre, un auteur, un &#233;diteur ou un ISBN.</em>');
		    }
		    x.removeClass("loading");
	    });
	} 
    }

    function _alert(x) {
	alert(x);
        $('#error').html(x);
        //$('#error').dialog({
        //    modal: true,
        //    buttons: { "OK": function() { $(this).dialog("close"); } }
        //});
    }

    function chooseBook(x) {
	    $('#add_step1').slideUp();
	    $('#chosenBookInfos').remove();
	    $('#choosenBook').prepend('<div id="chosenBookInfos">'+$('#chosen_' + x).html()+'</div>');
	    $('#review_ean').val(x);
	    $('#add_step2').slideDown();
    }
    
    function updateReview() {
	user_uid = $('#user_uid').val();
	var review_id = $('#update_review_id').val();
	var review_url = $('#update_review_url').val();
	var review_excerpt = $('#update_review_excerpt').val();
	var review_score = $('#update_review_score').val();
	$.post("/x/log_review_add", {
		user_uid: ""+user_uid+"",
		review_id: ""+review_id+"",
		review_url: ""+review_url+"",
		review_score: ""+review_score+"",
		review_excerpt: ""+review_excerpt+""
		}, function(data) {
		if(data == 'OK') {
		    $('#reviewEdit').dialog('close');
		} else {
		    _alert(data);
		}
	    submitButton.removeAttr('disabled');
	});
    }

    $(document).ready(function() {
	
	function reloadEvents() {
	    // Infos contextuelles
	    $(".textcon").focus( function() {
		    var champ = $(this).attr("id");
		    $("#" + champ + "Context").show();
	    });
	    $(".textcon").blur( function() {
		    var champ = $(this).attr("id");
		    $("#" + champ + "Context").hide();
	    });
	    // Nombre de caracteres
	    $(".textcon").keyup( function() {
		    var champ = $(this).attr("id");
		    var count = $(this).val().length;
		    $('#' + champ + 'Count').html(count);
	    });
	}
	reloadEvents();
	
	user_uid = $('#user_uid').val();
	
	$("table").tablesorter();
	
    $('.add').click(function () {
		$("#add").dialog({
				title: "Ajouter une critique",
				modal: true,
				width: 1000,
				height: 500
		});
		var id = $(this).attr('id');
		if(id.indexOf('addBook') == -1) {
		    $("#add_step2").hide();
		    $("#add_step3").hide();
		    $("#add_step1").slideDown();
		    $("#review_url").val('');
		    $("#review_score").val('');
		} else {
		    var ean = id.split('_');
		    chooseBook(ean[1]);
		}
		
        });
        
        $("#addQueryForm").submit( function() { // si on presse une touche du clavier en étant dans le champ texte qui a pour id intervenant
                addLookup($("#addQuery"));
                return false;
	});
	
        $('.showMyReviews').click(function () {
	    $("#myReviews").dialog({
		title: "Mes critiques",
		modal: true,
		width: 1000,
		height: 500
	    });
        });
	
        $('.showMyVisits').click(function () {
	    $("#myVisits").dialog({
		title: "Mes visites",
		modal: true,
		width: 1000,
		height: 500
	    });
        });
	
	$("#reviewAddForm").submit (function () {
	    var review_url = $('#review_url').val();
	    var review_ean = $('#review_ean').val();
	    //var review_line = $('#review_line').val();
	    //var review_excerpt = $('#review_excerpt').val();
	    var review_score = $('#review_score').val();
	    var review_excerpt = $('#review_excerpt').val();
	    var submitButton = $('#submitReviewAddForm');
	    submitButton.attr('disabled','disabled');
	    $.post("/x/log_review_add", {
		user_uid: ""+user_uid+"",
		review_url: ""+review_url+"",
		review_ean: ""+review_ean+"",
		review_score: ""+review_score+"",
		review_excerpt: ""+review_excerpt+""
		}, function(data){ // on envoit la valeur du champ texte dans la variable post queryString au fichier ajax.php
		if(data.indexOf('OK') > -1) {
		    var code = data.split('::');
		    $("#citriq_widget").val(code[1]);
		    $("#citriq_widget_nojs").val(code[2]);
		    $("#add_step2").hide();
		    $("#add_step3").slideDown();
		    $("#review_excerpt").val('');
		} else {
			_alert(data);
		}
		submitButton.removeAttr('disabled');
	    });
	    return false;
	});
	
	// Editer une critique
	$(".updateReview").click( function() {
	    var review_id = $(this).parent().attr('id').split("_")[1];
	    $("#reviewEdit").load("/x/log_review_edit?user_uid="+user_uid+"&review_id="+review_id, function() { reloadEvents() }
	    ).dialog({
		title: 'Modifier une critique',
		modal: 'true',
		width: '850',
		height: '350',
		buttons: [{
		    text: "Enregistrer",
		    click: function() { updateReview(); }
		}]
	    });
	    reloadEvents();
	});
	
	// Supprimer une critique
	$(".deleteReview").click( function() {
	    var review_id = $(this).parent().attr('id').split("_")[1];
	    $.post("/x/log_review_delete", {
		user_uid: ""+user_uid+"",
		review_id: ""+review_id+""
		}, function(data) { // on envoit la valeur du champ texte dans la variable post queryString au fichier ajax.php
		    if(data == "OK") {
			$("#review_"+review_id).fadeOut();
		    } else {
			_alert(data);
		    }
		submitButton.removeAttr('disabled');
	    });
	    
	});
        
});