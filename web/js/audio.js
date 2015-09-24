var playerId = 'audioBox';

function playAudio(link) {
    var elem = document.getElementById(playerId);
    var content = '<div class="objPlayer"><audio controls autoplay loop><source src="'+link+'" type="audio/wav" ></audio></div>';
    elem.style.opacity='1';
    elem.style.visibility='visible';
    elem.innerHTML=content;
}

// Скрыть запись
function hideAudio() {
	var elem = document.getElementById(playerId);
	elem.style.visibility='hidden';
	elem.style.opacity='0';
	elem.innerHTML='';
}

