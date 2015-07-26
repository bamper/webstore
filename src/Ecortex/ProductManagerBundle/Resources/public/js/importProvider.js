/**
 * Created by Papoun on 27/07/2015.
 */
$(function(){
    $('#buttonImport').click(function(){
        // On récupère les élément du DOM
        var $inputFile = $('#inputFile');
        var $loadingBar = $('#loadingBar');
        var $traitementBar = $('#traitementBar');
        var $importProgressPathInput = $('#importProgressPath');

        //On reset les elements
        $('#percentUpload').html('0%');
        $('#percentTraitement').html('0%');
        $('#status').html('');
        $('#importOk').html('');
        $loadingBar
            .css('width', '0%')
            .text('');
        $traitementBar
            .css('width', '0%')
            .text('');


        // On récupère le fichier
        var file = $inputFile[0].files[0];

        // On crée un FormData, c'est ce qu'on va envoyer au serveur
        var data = new FormData();
        data.append('file', file);

        // On envoie la requête AJAX
        $.ajax({
            type: 'POST',
            async: true,
            url: $inputFile.data('target'),
            data: data,
            contentType: false,
            processData: false,
            cache: false,
            timeout: 600000,
            xhr: function() {
                var xhr = $.ajaxSettings.xhr();

                xhr.upload.addEventListener("progress", function(e){
                    $loadingBar
                        .css('width', e.loaded / e.total * 100 + '%');
                    $('#percentUpload')
                        .html(e.loaded / e.total * 100 + '%')
                    if (e.loaded / e.total * 100 >= 100) {
                        $loadingBar
                            .css('width', '100%')
                            .text(file.name);
                    }
                }, false);

                return xhr;
            },
            success: function(data) {
                $('#importOk').html(data);
                clearInterval(interval);
            },
            error: function(jqXHR) {
                $loadingBar
                    .css('width', '100%')
                    .removeClass('progress-bar-success')
                    .addClass('progress-bar-danger')
                    .text('erreur');
                $('#importOk').html(jqXHR.responseText);
                clearInterval(interval);
            }
        });
        var percent = 0;
        var interval = setInterval(function() {
            $.ajax({
                type: 'GET',
                async: true,
                dataType: "json",
                url: $importProgressPathInput.val(),
                timeout: 3000,
                cache:false,
                success: function (data) {
                    percent = data.percent;
                    $('#percentTraitement').html(data.percent+"%");
                    $('#traitementBar').css('width', data.percent+'%');
                    $('#status').html(
                        '<p>'+data.currentRow+' lignes traitées sur un total de '+ data.totalRow +'</p>'+
                        '<p>Temps passé: ' + Math.floor(data.elapseTime) + ' secondes | Estimation du temps restant: '+ Math.floor(data.remaining) + ' secondes</p>'+
                        '<p>Vitesse instantanée: ' + Math.floor((1/data.elementTime)*10)/10 + ' ligne(s)/seconde | Vitesse moyenne: ' + Math.floor((1/data.averageTime)*10)/10 + ' lignes(s)/seconde</p>')
                },
                error: function () {
                    $('#importOk').html('<p>La requête n\'a pas abouti</p>');
                }
            });
        },1000);
    });

});