  </main>
  </div>
  
  <!-- Footer -->
  <footer class="mt-0 py-3" style="background: #6c757d; color: #fff; border-top: 1px solid #5a6268;">
    <div class="container-fluid">
      <div class="text-center">
        <p class="mb-0">&copy; <?= date('Y') ?> <strong>Sameer Said Abdallah</strong>. All Rights Reserved.</p>
      </div>
    </div>
  </footer>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
    // simple clock
    function updateClock(){
      const el = document.getElementById('clock');
      if(!el) return;
      const now = new Date();
      el.textContent = now.toLocaleString();
    }
    setInterval(updateClock,1000);
    updateClock();
  </script>
</body>
</html>
