@auth
<script src="https://webcdn.ringover.com/resources/SDK/1.1.3/ringover-sdk.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.ringoverPhone) {
            return; // déjà initialisé (navigation Livewire sans rechargement complet)
        }

        window.ringoverPhone = new window.RingoverSDK({
            type: 'fixed',
            size: 'medium',
            position: { bottom: '90px', right: '20px' },
            trayicon: true,
            trayposition: { bottom: '20px', right: '20px' },
            animation: true,
        });

        window.ringoverPhone.generate();

        // Fonction globale réutilisable partout dans le CRM
        window.appelerAvecRingover = function (numero) {
            if (!numero) {
                return;
            }
            window.ringoverPhone.show();
            window.ringoverPhone.dial(numero);
        };
    });
</script>
@endauth