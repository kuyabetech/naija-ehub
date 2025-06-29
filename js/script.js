    // Sidebar Toggle
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('active');
    }

    // Accessibility
    function toggleAccessibilityPanel() {
      document.getElementById('accessibility-panel').classList.toggle('active');
    }

    function adjustFontSize(scale) {
      document.documentElement.style.setProperty('--font-size-base', `${scale}rem`);
      localStorage.setItem('font-size', scale);
    }

    function toggleHighContrast(isChecked) {
      document.body.setAttribute('data-contrast', isChecked ? 'high' : 'normal');
      localStorage.setItem('contrast', isChecked ? 'high' : 'normal');
    }

    // Load saved settings
    document.addEventListener('DOMContentLoaded', () => {
      const savedFontSize = localStorage.getItem('font-size') || '1';
      const savedContrast = localStorage.getItem('contrast') || 'normal';
      document.documentElement.style.setProperty('--font-size-base', `${savedFontSize}rem`);
      document.body.setAttribute('data-contrast', savedContrast);
      document.querySelector('input[type="checkbox"]').checked = savedContrast === 'high';
      updateWalletBalance();
    });

    // Fund Wallet Modal
    function openFundWalletModal() {
      document.getElementById('fundWalletModal').style.display = 'flex';
    }

    function closeFundWalletModal() {
      document.getElementById('fundWalletModal').style.display = 'none';
    }

    function fundWallet(event) {
      event.preventDefault();
      const amount = event.target.querySelector('input[type="number"]').value;
      alert(`Initiating payment of ₦${amount} via Monnify. (Mock payment)`);
      // Add Monnify payment API integration here
      closeFundWalletModal();
    }

    // Transaction Filtering
    function filterTransactions() {
      const typeFilter = document.getElementById('filterType').value;
      const statusFilter = document.getElementById('filterStatus').value;
      const dateFilter = document.getElementById('filterDate').value;
      const rows = document.querySelectorAll('#transactionTable tr');

      rows.forEach(row => {
        const type = row.getAttribute('data-type') || '';
        const status = row.getAttribute('data-status') || '';
        const date = row.getAttribute('data-date') || '';

        const typeMatch = typeFilter === 'all' || type === typeFilter;
        const statusMatch = statusFilter === 'all' || status === statusFilter;
        const dateMatch = !dateFilter || date === dateFilter;

        row.style.display = typeMatch && statusMatch && dateMatch ? '' : 'none';
      });
    }

    // Export Transactions as CSV
    function exportTransactions() {
      const rows = document.querySelectorAll('#transactionTable tr:not([style*="display: none"])');
      let csv = 'Date,Type,Amount,Status,Reference,Description\n';
      rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length) {
          const rowData = Array.from(cells).map(cell => `"${cell.textContent.trim().replace(/"/g, '""')}"`).join(',');
          csv += `${rowData}\n`;
        }
      });
      const blob = new Blob([csv], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'transactions.csv';
      a.click();
      window.URL.revokeObjectURL(url);
    }

    // Update Wallet Balance (Mock AJAX)
    function updateWalletBalance() {
      // Replace with actual AJAX call to backend
      fetch('/api/wallet-balance.php?user_id=<?php echo $user_id; ?>')
        .then(response => response.json())
        .then(data => {
          document.getElementById('walletBalance').textContent = `₦${Number(data.balance).toFixed(2)}`;
        })
        .catch(error => console.error('Error updating wallet balance:', error));
    }

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = "?logout=1";
    });