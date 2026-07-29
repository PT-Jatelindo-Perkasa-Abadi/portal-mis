$(document).ready(function() {

    function showLoading(){

        $('#loadingScreen').css('display','flex');

    }

    function hideLoading(){

        $('#loadingScreen').fadeOut(200);

    }

    app.FormValidation.init("#change-password-form");
    app.ValidatePassword.init('.password-validate');

    $("#ChngPass").on('click', function () {

        var form            = $(this).closest("form")[0];
        var currentPassword = $("#currentPassword").val().trim();
        var newPassword     = $("#newPassword").val().trim();
        var confirmPassword = $("#confirmPassword").val().trim();


        if (!form.checkValidity()) {

            form.reportValidity(); 
            return;
        }

        if (currentPassword === newPassword) {


            $("#newPassword")[0].reportValidity();
            $("#newPassword").focus();
            $("#term-password-2").show();
            $(".term-password ").hide();

            return;

        } else {

            $("#newPassword")[0].setCustomValidity("");
            $("#term-password-2").hide();

        }

         if (newPassword !== confirmPassword) {


            $("#confirmPassword")[0].reportValidity();
            $("#confirmPassword").focus();
            $("#term-password-3").show();
            $(".term-password ").hide();

            return;

        } else {

            $("#confirmPassword")[0].setCustomValidity("");
            $("#term-password-3").hide();

        }

        $.ajax({

            url: '/profile/index/changepass',
            type: 'POST',

            data: {
                sess: $('#sess').val(),
                email: $('#email').val(),
                currentPassword: $('#currentPassword').val(),
                newPassword: $('#newPassword').val(),
                ip: $('#ip').val(),
                useragent: $('#useragent').val()
            },

            beforeSend:function(){
                showLoading();
            },

            success:function(res){

                var data = JSON.parse(
                    $($.parseHTML(res)).find('.content').text().trim()
                );

                if (data.msg?.[0]?.ERROR) {

                    $('#modalChangePassword').hide();
                    $('#errorModal').modal('show');
                    $('#errorModal .modal-body .text-response').html(data.msg[0].ERROR);

                    return;
                }
                else{

                        $('#modalChangePassword').hide();
                        $('#modalSuccess').modal('show');
                        $('#modalSuccess .modal-body .text-response').html(data.msg[0].ERROR);

                        return;

                }

            },

            complete:function(){

                hideLoading();

            }

        });
    });

    $('#btnModalErrorok').on('click', function () {
        location.reload();
    });

    $('#btnModalSuccessok').on('click', function () {
        window.location.href = '/auth/logout';
    });


});