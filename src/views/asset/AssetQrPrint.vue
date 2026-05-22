<template>
  <div class="modal fade" id="assetQrModal" tabindex="-1" ref="modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-primary text-white border-0">
          <h5 class="modal-title fw-bold">
            <i class="bi bi-printer me-2"></i>พิมพ์ป้ายครุภัณฑ์
          </h5>
          <button
            type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body p-4 text-center">
          <div
            id="printable-area"
            class="print-label-container mx-auto mb-4"
            v-if="asset"
          >
            <table class="label-table">
              <tbody>
                <!-- แถว 1: เลขครุภัณฑ์ | วันที่จัดซื้อ -->
                <tr>
                  <td class="col-key">เลขครุภัณฑ์</td>
                  <td class="col-sep">:</td>
                  <td class="col-val col-val-wide">{{ asset.asset_code || '-' }}</td>
                  <td class="col-gap"></td>
                  <td class="col-key">วันที่จัดซื้อ</td>
                  <td class="col-sep">:</td>
                  <td class="col-val">{{ asset.purchase_date || '-' }}</td>
                </tr>
                <!-- แถว 2: วิธีการได้รับ | บริษัท -->
                <tr>
                  <td class="col-key">วิธีการได้รับ</td>
                  <td class="col-sep">:</td>
                  <td class="col-val col-val-wide">{{ asset.acquisition_method || '-' }}</td>
                  <td class="col-gap"></td>
                  <td class="col-key">บริษัท</td>
                  <td class="col-sep">:</td>
                  <td class="col-val">{{ asset.source || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="d-grid gap-2">
            <button class="btn btn-primary rounded-pill py-2 fw-bold" @click="printLabel">
              <i class="bi bi-printer me-2"></i> สั่งพิมพ์
            </button>
            <button class="btn btn-outline-secondary rounded-pill py-2" data-bs-dismiss="modal">
              ยกเลิก
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';

export default {
  name: 'AssetQrPrint',
  data() {
    return {
      asset: null,
      bsModal: null
    };
  },
  mounted() {
    this.bsModal = new Modal(this.$refs.modal);
  },
  methods: {
    open(asset) {
      this.asset = asset;
      this.bsModal.show();
    },
    printLabel() {
      const printContents = document.getElementById('printable-area').innerHTML;

      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>Print - ${this.asset.asset_code}</title>
            <style>
              * { box-sizing: border-box; margin: 0; padding: 0; }
              body {
                font-family: 'Tahoma', Arial, sans-serif;
                font-size: 8.5pt;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
              }
              .print-label-container {
                width: 120mm;
                height: 18mm;
                border: 1.5px solid #000;
                padding: 1mm 2mm;
                display: flex;
                align-items: center;
                overflow: hidden;
              }
              .label-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 8.5pt;
                line-height: 1.25;
                table-layout: fixed;
              }
              .col-key {
                white-space: nowrap;
                padding-right: 1mm;
                color: #000;
                width: 16mm;
              }
              .col-sep {
                white-space: nowrap;
                padding-right: 1mm;
                color: #000;
                width: 2mm;
              }
              .col-val {
                color: #000;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
              }
              .col-val-wide { width: 35mm; }
              .col-gap { width: 3mm; }
              @media print {
                body { margin: 0; padding: 0; }
                .print-label-container { border: 1.5px solid #000; }
                @page { size: 120mm 18mm; margin: 0; }
              }
            </style>
          </head>
          <body>
            <div class="print-label-container">
              ${printContents}
            </div>
            <script>
              document.fonts.ready.then(() => {
                setTimeout(() => {
                  window.print();
                  window.close();
                }, 300);
              });
            <\/script>
          </body>
        </html>
      `);
      printWindow.document.close();
    }
  }
};
</script>

<style scoped>
.print-label-container {
  width: 120mm;
  height: 18mm;
  border: 1.5px solid #333;
  padding: 1mm 2mm;
  display: flex;
  align-items: center;
  background: white;
  overflow: hidden;
}

.label-table {
  width: 100%;
  border-collapse: collapse;
  font-family: 'Tahoma', Arial, sans-serif;
  font-size: 8.5pt;
  line-height: 1.25;
  color: #000;
  table-layout: fixed;
}

.col-key {
  white-space: nowrap;
  padding-right: 1mm;
  width: 16mm;
}

.col-sep {
  white-space: nowrap;
  padding-right: 1mm;
  width: 2mm;
}

.col-val {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.col-val-wide {
  width: 35mm;
}

.col-gap {
  width: 3mm;
}
</style>
