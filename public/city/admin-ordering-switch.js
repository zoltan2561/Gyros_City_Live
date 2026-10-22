(function () {
    'use strict';
    window.changeStatus = async function (status, url) {
        const checkbox = document.getElementById('open-close-switch');
        const restore = () => { if (checkbox) checkbox.checked = Number(status) === 2; };
        const confirmation = await swalWithBootstrapButtons.fire({icon: 'warning', title: are_you_sure,
            showCancelButton: true, confirmButtonText: yes, cancelButtonText: no, reverseButtons: true});
        if (!confirmation.isConfirmed) { restore(); return; }
        try {
            const response = await fetch(url, {method: 'POST', body: new URLSearchParams({status}), headers: {
                Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            }});
            if (!response.ok) throw new Error('Nem sikerült módosítani a rendelésfogadást.');
            location.reload();
        } catch (error) { restore(); toastr.error(error.message); }
    };
})();
