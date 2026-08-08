@auth
<script src="https://webcdn.ringover.com/resources/SDK/1.1.3/ringover-sdk.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sur la page Phoning Workflow, Ringover est incrusté dans la colonne droite
        // via un SDK dédié. On ne doit PAS lancer le widget flottant global ici.
        if (window.location.pathname.includes('phoning-workflow')) {
            return;
        }

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

    // Sur navigation Livewire (SPA), vérifier de nouveau si on entre/sort du workflow
    document.addEventListener('livewire:navigated', function () {
        if (window.location.pathname.includes('phoning-workflow')) {
            // On est arrivé sur le workflow : détruire le widget flottant s'il existe
            if (window.ringoverPhone && typeof window.ringoverPhone.destroy === 'function' && !window.ringoverPhone.__placeholder) {
                try { window.ringoverPhone.destroy(); } catch (e) {}
                window.ringoverPhone = null;
            }
            return;
        }

        // On est sur une autre page : créer le widget flottant si pas déjà là
        if (window.ringoverPhone && !window.ringoverPhone.__placeholder) {
            return;
        }

        if (typeof window.RingoverSDK !== 'function') { return; }

        window.ringoverPhone = new window.RingoverSDK({
            type: 'fixed',
            size: 'medium',
            position: { bottom: '90px', right: '20px' },
            trayicon: true,
            trayposition: { bottom: '20px', right: '20px' },
            animation: true,
        });

        window.ringoverPhone.generate();

        window.appelerAvecRingover = function (numero) {
            if (!numero) { return; }
            window.ringoverPhone.show();
            window.ringoverPhone.dial(numero);
        };
    });
</script>
@endauth