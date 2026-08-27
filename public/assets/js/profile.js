$(document).ready(function() {
    if ($('#mitraSection').length) {
        $.ajax({
            url: '/profile',
            type: 'POST',
            data: {},
            success: function (response) {
                const { nama_technical_provider } = response.data[0];
                $('#mitraSection #mitraName').text(nama_technical_provider ?? "-");
            },
            error: function() {
                $('#mitraSection #mitraName').text("-");
            }
        });
    }

    function showLoading() {
        $('#loadingScreen').css('display','flex');
    }

    function hideLoading() {
        $('#loadingScreen').fadeOut(200);
    }

    app.FormValidation.init("#change-password-form");

    $('#change-password-form').on('submit', function(e) {
        e.preventDefault();
    });

    $('.password-validate').on('keyup', function(e) {
        if (/\s/.test(e.key)) {
            e.preventDefault();
        }

        if ($('.term-password-err').is(':visible')) {
            $(".term-password-err").hide();
        }
    });

    $('.empty-validate').on('input', function() {
        this.value = this.value.replace(/\s/g, '');
    });

    $("#ChngPass").on('click', function() {
        let form            = $(this).closest("form")[0];
        let currentPassword = $("#currentPassword").val().trim();
        let newPassword     = $("#newPassword").val().trim();
        let confirmPassword = $("#confirmPassword").val().trim();


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
                currentPassword: $('#currentPassword').val(),
                newPassword: $('#newPassword').val()
            },
            beforeSend: function() {
                showLoading();
            },
            success: function(res) {
                let data = res.data;

                if (data.msg?.[0]?.ERROR) {
                    $('#modalChangePassword').hide();
                    $('#errorModal').modal('show');
                    $('#errorModal .modal-body .text-response').html(data.msg[0].ERROR);

                    return;
                }
                else {
                    $('#modalChangePassword').hide();
                    $('#modalSuccess').modal('show');
                    $('#modalSuccess .modal-body .text-response').html(data.msg[0].ERROR);

                    return;
                }
            },
            complete: function() {
                hideLoading();
            }
        });
    });

    $('#btnModalErrorok').on('click', function() {
        location.reload();
    });

    $('#btnModalSuccessok').on('click', function() {
        window.location.href = '/auth/logout';
    });

    $('#modalChangePassword').on('hidden.bs.modal', function() {
        $('#change-password-form').trigger('reset');
        $('.term-password').css('display', 'none');
        $('.empty-validate').removeClass('error');
    });
});