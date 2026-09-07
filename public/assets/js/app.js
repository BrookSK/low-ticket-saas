/* LowTicket SaaS - JS base do painel */
(function () {
    'use strict';

    // Toggle da sidebar no mobile
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-sidebar-toggle]');
        if (toggle) {
            document.querySelector('.sidebar')?.classList.toggle('open');
            document.querySelector('.sidebar-backdrop')?.classList.toggle('open');
        }
        if (e.target.classList.contains('sidebar-backdrop')) {
            document.querySelector('.sidebar')?.classList.remove('open');
            e.target.classList.remove('open');
        }

        // Dropdowns
        var dd = e.target.closest('[data-dropdown]');
        document.querySelectorAll('.dropdown-menu.open').forEach(function (m) {
            if (!dd || dd.nextElementSibling !== m) m.classList.remove('open');
        });
        if (dd) {
            dd.nextElementSibling?.classList.toggle('open');
        }
    });

    // Confirmacao em acoes destrutivas
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.dataset.confirm) {
            if (!window.confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        }
    });

    // Auto-dismiss de alerts
    setTimeout(function () {
        document.querySelectorAll('.alert[data-dismiss]').forEach(function (a) {
            a.style.transition = 'opacity .4s';
            a.style.opacity = '0';
            setTimeout(function () { a.remove(); }, 400);
        });
    }, 5000);

    // Helper global para formatar moeda BRL no client
    window.formatBRL = function (value) {
        return (Number(value) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };
})();
