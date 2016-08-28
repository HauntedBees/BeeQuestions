var hidden;
$(document).ready(function() {
	$("#answersTab .filteroption a").on("click", function() { return FilterClick($(this), "getUserAnswers.php"); });
	$("#questionsTab .filteroption a").on("click", function() { return FilterClick($(this), "getUserQuestions.php"); });
	$("#historyTab .filteroption a").on("click", function() { return FilterClick($(this), "getUserHistory.php"); });
	$("#frontpagecontent .filteroption a").on("click", function() { return FilterClick($(this), "getFrontPageAnswers.php"); });
	$("#tagcontent .filteroption a").on("click", function() { return FilterClick($(this), "getTagAnswers.php", { tag: $("#tagName").attr("data-id")}); });
	
	$(".chooseQuestion").on("click", function() { return LikeClick($(this).closest(".question"), "chooseQuestion.php", $(this).closest(".question").attr("data-id"), "Question chosen successfully! You gained 5 points!", false, true); });
	$(".likeQuestion").on("click", function() { return LikeClick($(this).closest(".question"), "likeQuestion.php", $(this).closest(".question").attr("data-id"), "Question liked successfully!"); });
	$(".likeAnswer").on("click", function() { return LikeClick($("#answerInfo"), "likeAnswer.php", $("#answerInfo").attr("data-id"), "Answer liked successfully!"); });
	$(".reportQuestion").on("click", function() { return LikeClick($(this).closest(".question"), "reportQuestion.php", $(this).closest(".question").attr("data-id"), "Question reported successfully!", true); });
	$(".reportAnswer").on("click", function() { return LikeClick($("#answerInfo"), "reportAnswer.php", $("#answerInfo").attr("data-id"), "Answer reported successfully!", true); });
	$(document).on("click", ".loadMore", function() {
		var $filter = $(this).closest(".maincontent,.tab-pane").find(".filteroption.active > a");
		var offset = parseInt($(this).attr("data-offset")) + 10;
		$(this).attr("disabled", "disabled").text("Loading...");
		if($(this).hasClass("frontpage")) { return FilterClick($filter, "getFrontPageAnswers.php", { offset: offset }, true); }
		if($(this).hasClass("tags")) { return FilterClick($filter, "getTagAnswers.php", { offset: offset, tag: $("#tagName").attr("data-id") }, true); }
		if($(this).hasClass("userHistory")) { return FilterClick($filter, "getUserHistory.php", { offset: offset }, true); }
		if($(this).hasClass("userAnswers")) { return FilterClick($filter, "getUserAnswers.php", { offset: offset }, true); }
		if($(this).hasClass("userQuestions")) { return FilterClick($filter, "getUserQuestions.php", { offset: offset }, true); }
	});
	$("#submitAnswer").on("click", function() {
		var aval = $.trim($("#txtAnswer").val());
		if(aval.length == 0 || aval.length > 400) { return CreateNotification("danger", "Please enter a valid answer (less than 400 characters)."); }
		var tval = $.trim($("#txtTags").val());
		if(tval.length == 0) { return CreateNotification("danger", "Please enter one or more tags."); }
		if(tval.length > 100) { return CreateNotification("danger", "That's way too many tags."); }
		$.ajax({
			type: "POST", dataType: "JSON", 
			url: "ajax/giveAnswer.php",
			data: { answer: aval, tags: tval }, 
			success: function(data) {
				if(data.status) {
					CreateNotification("success", "Answer posted successfully!");
					LevelNotification(data);
					window.location = "viewAnswer.php?answer=" + data.id;
				} else {
					CreateNotification("warning", data.errorMessage);
				}
			}, 
			error: function() { CreateNotification("danger", "Something went wrong! Please try again later."); }
		});
		return false;
	});
	$("#submitQuestion").on("click", function() {
		var qval = $.trim($("#txtNewQuestion").val());
		if(qval.length == 0 || qval.length > 400) { return CreateNotification("danger", "Please enter a valid question (less than 400 characters)."); }
		$.ajax({
			type: "POST", dataType: "JSON", 
			url: "ajax/giveQuestion.php",
			data: { answer: $("#answerInfo").attr("data-id"), question: qval }, 
			success: function(data) {
				if(data.status) {
					CreateNotification("success", "Question posted successfully!");
					$("#maincontent").append(data.html);
					LevelNotification(data);
				} else {
					CreateNotification("warning", data.errorMessage);
				}
			}, 
			error: function() { CreateNotification("danger", "Something went wrong! Please try again later."); }
		});
		return false;
	});
	SetUpNameChanging();
	if (typeof document.hidden !== "undefined") { hidden = "hidden"; }
	else if (typeof document.mozHidden !== "undefined") { hidden = "mozHidden"; }
	else if (typeof document.msHidden !== "undefined") { hidden = "msHidden"; }
	else if (typeof document.webkitHidden !== "undefined") { hidden = "webkitHidden"; }
	window.setInterval(GetNotifications, 60000);
	GetNotifications();
});

function GetNotifications() {
	if(document[hidden]) { return; }
	$.ajax({
		type: "GET", dataType: "JSON", url: "ajax/getNotifications.php",
		success: function(data) { for(var i = 0; i < data.length; i++) { CreateNotification("success", data[i].notif, data[i].url); } }
	});
}
function LevelNotification(data) {
	if(data.pchange > 0) { CreateNotification("success", "You got " + data.pchange + " points!"); }
	else if(data.pchange < 0) { CreateNotification("warning", "You used " + (-data.pchange) + " points."); }
	if(data.lchange > 0) { CreateNotification("success", "You have reached level " + data.level + "!"); }
	else if(data.lchange < 0) { CreateNotification("warning", "You have moved down to level " + data.level + "."); }
}
function FilterClick($clicked, path, addtl, isLoadMore) {
	var params = {
		offset: 0, 
		filter: $clicked.attr("data-type"),
		user: $("#username").attr("data-id")
	};
	if(addtl !== undefined) { $.extend(params, addtl); }
	$.ajax({
		type: "POST", dataType: "HTML", 
		url: "ajax/" + path, 
		context: $clicked.parent(),
		data: params, 
		success: function(data) {
			var $top = $(this).closest(".topfilter");
			$top.find(".filteroption.active").removeClass("active");
			$(this).addClass("active");
			if(isLoadMore) {
				$(".loadMore").remove();
				var existing = $top.parent().find(".listContent").html();
				$top.parent().find(".listContent").html(existing + data);
			} else {
				$top.parent().find(".listContent").html(data);
			}
		}, 
		error: function() { CreateNotification("danger", "Something went wrong! Please try again later."); }
	});
	return false;
}
function LikeClick($clicked, path, id, successMessage, report, choose) {
	$.ajax({
		type: "POST", dataType: "JSON", 
		url: "ajax/" + path, 
		context: $clicked,
		data: { id: id }, 
		success: function(data) {
			if(data.status) {
				CreateNotification("success", successMessage);
				if(report) {
					$(this).find(".reportBtn").attr("disabled", "disabled");
				} else {
					$(this).find(".score").text(data.count + " like" + (data.count == 1 ? "" : "s"));
					$(this).find(".likeBtn").attr("disabled", "disabled");
				}
				if(choose) { setTimeout(function() { location.reload(); }, 5000); }
			} else {
				CreateNotification("warning", data.errorMessage);
			}
		}, 
		error: function() {
			CreateNotification("danger", "Something went wrong! Please try again later.");
		}
	});
	return false;
}
function CreateNotification(type, message, url) { 
	if(url == undefined) {
		$(".top-right").notify({ type: type, message: message }).show();
	} else {
		$(".top-right").notify({ type: type, message: "<a href ='" + url + "'>" + message + "</a>" }).show();
	}
}
function SetUpNameChanging() {
	$("#nameInput").on("keypress", function(e) { 
		if(e.keyCode == 13) { $("#saveName").trigger("click"); }
		else if(e.keyCode == 27) { $("#cancelName").trigger("click"); }
	});
	$("#changeName").on("click", function() { $("#showName").hide(); $("#editName").show(); $("#nameInput").focus(); });
	$("#cancelName").on("click", function() { $("#showName").show(); $("#editName").hide(); });
	$("#saveName").on("click", function() {
		var displayName = $.trim($("#nameInput").val());
		if(displayName == "" || !displayName.match(/^[A-Za-z0-9_\-\s]+$/ || displayName.length > 20)) {
			CreateNotification("warning", "Please enter a valid display name (alphanumeric characters only, less than 20 characters).");
			return;
		}
		$.ajax({
			type: "POST", dataType: "JSON", 
			url: "ajax/changeName.php", 
			data: {"name": $("#nameInput").val() }, 
			success: function(data) {
				if(data.status) {
					CreateNotification("success", "Name changed successfully!");
					$("#username").text($.trim($("#nameInput").val()));
					$("#showName").show(); $("#editName").hide();
				} else {
					CreateNotification("warning", data.errorMessage);
				}
			}, 
			error: function() {
				CreateNotification("danger", "Something went wrong! Please try again later.");
			}
		});
	});
}