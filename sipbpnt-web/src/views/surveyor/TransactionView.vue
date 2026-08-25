<script setup lang="ts">
import {
  ReceiptText,
} from '@lucide/vue'
</script>

<template>
  <section class="page">
    <header class="page-header">
      <span>BPNT</span>
      <h1>Transaksi</h1>
      <p>
        Pencatatan transaksi KPM.
      </p>
    </header>

    <article class="placeholder">
      <div class="placeholder-icon">
        <ReceiptText
          :size="31"
          :stroke-width="1.8"
        />
      </div>

      <strong>Modul transaksi belum tersedia</strong>

      <p>
        Backend transaksi dibangun setelah
        business rule multi-transaksi dan
        perlindungan saldo difinalkan.
      </p>
    </article>
  </section>
</template>

<style scoped>
.page {
  display: grid;
  gap: 18px;
}

.page-header span {
  color: #c12723;
  font-size: 12px;
  font-weight: 750;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.page-header h1 {
  margin: 4px 0;
  color: #173f37;
  font-size: 27px;
}

.page-header p {
  margin: 0;
  color: #72847e;
  font-size: 14px;
}

.placeholder {
  display: grid;
  justify-items: center;

  padding: 34px 22px;

  border: 1px dashed #cdded8;
  border-radius: 22px;
  background: #ffffff;
  text-align: center;
}

.placeholder-icon {
  display: grid;
  place-items: center;

  width: 60px;
  height: 60px;
  margin-bottom: 14px;

  border-radius: 20px;
  background: #fff3de;
  color: #c36b00;
}

.placeholder strong {
  color: #244b43;
  font-size: 16px;
}

.placeholder p {
  max-width: 310px;
  margin: 7px 0 0;
  color: #778984;
  font-size: 13px;
  line-height: 1.6;
}
</style>