<template>
  <div class="warfarin-calculator">
    <!-- Header Banner -->
    <div class="calc-header p-3 p-md-4 rounded-4 mb-4 text-white shadow-sm position-relative overflow-hidden">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 position-relative z-1">
        <div class="d-flex align-items-center gap-3">
          <div class="header-icon-circle bg-white text-danger shadow-sm">
            <i class="bi bi-calculator-fill fs-3"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <h4 class="fw-bold mb-0">เครื่องมือคำนวณปรับขนาดยา Warfarin (INR Nomogram)</h4>
              <span class="badge bg-warning text-dark fw-bold rounded-pill px-3 py-1">HAD Protocol</span>
            </div>
            <small class="text-white-50">
              คำนวณขนาดยาใหม่ (ค่า C) และจำนวนเม็ดยาที่ต้องจ่าย จากค่าผลเลือด (A) และขนาดยาเดิม (B)
            </small>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
          <button
            type="button"
            class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-2"
            @click="openPrintModal()"
            title="พิมพ์ตารางการรับประทานยา (WF SmartDose)"
          >
            <i class="bi bi-printer-fill"></i>
            <span>พิมพ์ตารางยา</span>
          </button>
          <button
            type="button"
            class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-2"
            @click="openHistoryModal"
          >
            <i class="bi bi-clock-history"></i>
            <span>ประวัติการคำนวณ</span>
            <span v-if="historyTotalCount > 0" class="badge bg-warning text-dark">{{ historyTotalCount }}</span>
          </button>
          <button class="btn btn-sm btn-light rounded-pill px-3 fw-bold text-dark shadow-sm" @click="resetDefaults">
            <i class="bi bi-arrow-counterclockwise me-1"></i>รีเซ็ตค่า
          </button>
        </div>
      </div>
      <!-- Background subtle decorative shapes -->
      <div class="bg-shape shape-1"></div>
      <div class="bg-shape shape-2"></div>
    </div>

    <!-- Main Grid: Left Inputs / Right Results -->
    <div class="row g-4 mb-4">
      <!-- 1. Input Panel -->
      <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary-subtle text-primary rounded-circle p-2">
                <i class="bi bi-pencil-fill"></i>
              </span>
              <strong class="text-dark">กรอกข้อมูลผู้ป่วย & ขนาดยา</strong>
            </div>
            <span class="badge bg-light text-muted border">Input Data</span>
          </div>

          <div class="card-body p-3 p-md-4">
            <!-- Patient Info Section (For Recording History) -->
            <div class="p-3 rounded-4 mb-4 border bg-light shadow-xs">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-bold text-dark d-flex align-items-center gap-1">
                  <i class="bi bi-person-badge-fill text-primary"></i> ข้อมูลผู้ป่วย & ผู้บันทึก
                </span>
                <span class="badge bg-white text-muted border small">ประวัติ & HOSxP</span>
              </div>

              <!-- HN & Auto Lookup -->
              <div class="row g-2 mb-2">
                <div class="col-12 col-sm-5">
                  <label class="form-label small text-muted mb-1">HN ผู้ป่วย:</label>
                  <div class="input-group input-group-sm">
                    <input
                      type="text"
                      class="form-control font-monospace fw-bold"
                      placeholder="เช่น 123456"
                      v-model="patientHn"
                      @keyup.enter="lookupPatient"
                    />
                    <button
                      class="btn btn-primary"
                      type="button"
                      @click="lookupPatient"
                      :disabled="loadingPatient || !patientHn"
                      title="ค้นหาชื่อผู้ป่วยจาก HOSxP"
                    >
                      <span v-if="loadingPatient" class="spinner-border spinner-border-sm" role="status"></span>
                      <i v-else class="bi bi-search"></i>
                    </button>
                  </div>
                </div>

                <div class="col-12 col-sm-7">
                  <label class="form-label small text-muted mb-1">ชื่อ-นามสกุล ผู้ป่วย:</label>
                  <input
                    type="text"
                    class="form-control form-control-sm font-monospace"
                    placeholder="พิมพ์ชื่อหรือค้นจาก HN"
                    v-model="patientName"
                  />
                </div>
              </div>

              <!-- Recorded By & Note -->
              <div class="row g-2">
                <div class="col-12 col-sm-6">
                  <label class="form-label small text-muted mb-1">ผู้บันทึก / เภสัชกร:</label>
                  <input
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="ระบุชื่อผู้บันทึก"
                    v-model="recordedBy"
                  />
                </div>
                <div class="col-12 col-sm-6">
                  <label class="form-label small text-muted mb-1">หมายเหตุ (Note):</label>
                  <input
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="เช่น นัด 2 สัปดาห์, ปรับตามแพทย์"
                    v-model="calculationNote"
                  />
                </div>
              </div>
            </div>

            <!-- Input A: INR Blood Result -->
            <div class="mb-4">
              <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                <span>
                  <span class="badge bg-primary text-white rounded-circle me-1 px-2 py-1">A</span>
                  ค่าผลเลือด (INR ปัจจุบัน)
                </span>
                <span v-if="inrStatusBadge" :class="['badge rounded-pill px-2 py-1 small', inrStatusBadge.class]">
                  {{ inrStatusBadge.text }}
                </span>
              </label>
              <div class="input-group input-group-lg shadow-xs">
                <span class="input-group-text bg-light text-secondary border-end-0">
                  <i class="bi bi-droplet-half text-danger"></i>
                </span>
                <input
                  type="number"
                  step="0.05"
                  min="0"
                  max="20"
                  class="form-control form-control-lg fw-bold border-start-0 font-monospace text-primary"
                  v-model.number="inrValue"
                  placeholder="เช่น 1.4, 2.5, 4.2"
                />
                <span class="input-group-text bg-light text-muted">INR</span>
              </div>

              <!-- Quick Presets for INR -->
              <div class="d-flex gap-1 flex-wrap mt-2 align-items-center">
                <small class="text-muted me-1">ตัวอย่างทดสอบ:</small>
                <button
                  v-for="p in inrPresets"
                  :key="p.val"
                  type="button"
                  class="btn btn-xs rounded-pill px-2 py-0 border"
                  :class="inrValue === p.val ? 'btn-primary text-white' : 'btn-light text-secondary'"
                  @click="inrValue = p.val"
                >
                  {{ p.label }}
                </button>
              </div>
            </div>

            <!-- Input B: Current Dose -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold text-dark mb-0 d-flex align-items-center gap-1">
                  <span class="badge bg-primary text-white rounded-circle me-1 px-2 py-1">B</span>
                  ขนาดยาเดิมของผู้ป่วย (Current Dose)
                </label>
                <div class="d-flex align-items-center gap-2">
                  <button
                    type="button"
                    class="btn btn-xs rounded-pill px-2 py-1 shadow-2xs d-flex align-items-center gap-1"
                    :class="showDoseCalculator ? 'btn-primary text-white' : 'btn-outline-primary'"
                    @click="showDoseCalculator = !showDoseCalculator"
                    title="เปิด/ปิด เครื่องมือคำนวณจากขนาดเม็ดยาและจำนวนวัน"
                  >
                    <i class="bi" :class="showDoseCalculator ? 'bi-chevron-up' : 'bi-calculator'"></i>
                    <span class="d-none d-sm-inline">{{ showDoseCalculator ? 'ซ่อนเครื่องมือคำนวณ' : 'เครื่องมือช่วยคำนวณ' }}</span>
                  </button>
                  <div class="btn-group btn-group-sm" role="group">
                    <button
                      type="button"
                      class="btn btn-xs"
                      :class="doseUnit === 'weekly' ? 'btn-primary' : 'btn-outline-secondary'"
                      @click="setDoseUnit('weekly')"
                    >
                      มก./สัปดาห์
                    </button>
                    <button
                      type="button"
                      class="btn btn-xs"
                      :class="doseUnit === 'daily' ? 'btn-primary' : 'btn-outline-secondary'"
                      @click="setDoseUnit('daily')"
                    >
                      มก./วัน
                    </button>
                  </div>
                </div>
              </div>

              <!-- Dose Calculator Helper Card (กรอกขนาดยา * จำนวนวัน รวม 2 ขนาด) -->
              <div
                v-if="showDoseCalculator"
                class="dose-helper-card p-3 rounded-4 mb-2 border shadow-xs position-relative"
              >
                <!-- Tool Header -->
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                  <div class="d-flex align-items-center gap-2">
                    <div class="badge bg-warning-subtle text-warning-emphasis rounded-circle p-1">
                      <i class="bi bi-calculator-fill"></i>
                    </div>
                    <div>
                      <div class="fw-bold text-dark small">เครื่องมือคำนวณขนาดยาเดิม (รายสัปดาห์)</div>
                      <div class="text-muted" style="font-size: 11px;">
                        กรอกขนาดเม็ดยา × จำนวนวัน แล้วรวมทั้ง 2 ขนาดยาเข้าด้วยกัน
                      </div>
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-1">
                    <button
                      v-if="doseCalcTotalDays > 0"
                      type="button"
                      class="btn btn-link btn-xs text-secondary text-decoration-none p-0 d-flex align-items-center gap-1"
                      @click="clearDoseCalc"
                      title="ล้างค่าที่กรอกในเครื่องมือคำนวณ"
                    >
                      <i class="bi bi-arrow-counterclockwise"></i>
                      <span>รีเซ็ต</span>
                    </button>
                  </div>
                </div>

                <!-- Table Rows + Curly Bracket + Total Card -->
                <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
                  <!-- The 2 Rows -->
                  <div class="flex-grow-1 w-100">
                    <!-- Column Headers -->
                    <div class="d-flex align-items-center gap-1 text-center small text-muted fw-bold mb-1 px-1">
                      <div style="width: 46px;">ขนาด</div>
                      <div class="flex-fill" style="min-width: 60px;">ขนาดเม็ดยา</div>
                      <div style="width: 14px;"></div>
                      <div class="flex-fill" style="min-width: 60px;">จำนวนวัน</div>
                      <div style="width: 14px;"></div>
                      <div class="flex-fill" style="min-width: 60px;">รวม</div>
                    </div>

                    <!-- Row 1: 2mg -->
                    <div class="d-flex align-items-center gap-1 mb-2">
                      <div style="width: 46px;" class="text-center">
                        <span class="badge rounded-pill px-2 py-1 pill-orange text-dark fw-bold w-100" title="Warfarin 2 mg (เม็ดสีส้ม)">
                          2mg.
                        </span>
                      </div>
                      <div class="flex-fill" style="min-width: 60px;">
                        <input
                          type="number"
                          step="0.5"
                          min="0"
                          class="form-control form-control-sm text-center font-monospace fw-bold bg-light shadow-2xs"
                          v-model.number="doseCalc2mg_dose"
                          @input="onDoseCalcInput"
                          placeholder="2"
                          title="ขนาดยาต่อวัน (มก.) เช่น 2, 4, 1"
                        />
                      </div>
                      <div style="width: 14px;" class="text-center fw-bold text-muted small">✕</div>
                      <div class="flex-fill" style="min-width: 60px;">
                        <input
                          type="number"
                          step="0.5"
                          min="0"
                          max="7"
                          class="form-control form-control-sm text-center font-monospace fw-bold bg-white border-primary-subtle shadow-2xs"
                          v-model.number="doseCalc2mg_days"
                          @input="onDoseCalcInput"
                          placeholder="0 วัน"
                          title="จำนวนวันที่รับประทาน (0-7 วัน)"
                        />
                      </div>
                      <div style="width: 14px;" class="text-center fw-bold text-muted small">=</div>
                      <div class="flex-fill" style="min-width: 60px;">
                        <div
                          class="form-control form-control-sm text-center font-monospace fw-bold border"
                          :class="doseCalc2mg_total > 0 ? 'bg-primary-subtle border-primary-subtle text-primary' : 'bg-light text-muted'"
                          title="รวมขนาดยา 2mg"
                        >
                          {{ doseCalc2mg_total > 0 ? doseCalc2mg_total.toFixed(1) : '-' }}
                        </div>
                      </div>
                    </div>

                    <!-- Row 2: 3mg -->
                    <div class="d-flex align-items-center gap-1">
                      <div style="width: 46px;" class="text-center">
                        <span class="badge rounded-pill px-2 py-1 pill-blue text-dark fw-bold w-100" title="Warfarin 3 mg (เม็ดสีฟ้า)">
                          3mg.
                        </span>
                      </div>
                      <div class="flex-fill" style="min-width: 60px;">
                        <input
                          type="number"
                          step="0.5"
                          min="0"
                          class="form-control form-control-sm text-center font-monospace fw-bold bg-light shadow-2xs"
                          v-model.number="doseCalc3mg_dose"
                          @input="onDoseCalcInput"
                          placeholder="3"
                          title="ขนาดยาต่อวัน (มก.) เช่น 3, 6, 1.5"
                        />
                      </div>
                      <div style="width: 14px;" class="text-center fw-bold text-muted small">✕</div>
                      <div class="flex-fill" style="min-width: 60px;">
                        <input
                          type="number"
                          step="0.5"
                          min="0"
                          max="7"
                          class="form-control form-control-sm text-center font-monospace fw-bold bg-white border-primary-subtle shadow-2xs"
                          v-model.number="doseCalc3mg_days"
                          @input="onDoseCalcInput"
                          placeholder="0 วัน"
                          title="จำนวนวันที่รับประทาน (0-7 วัน)"
                        />
                      </div>
                      <div style="width: 14px;" class="text-center fw-bold text-muted small">=</div>
                      <div class="flex-fill" style="min-width: 60px;">
                        <div
                          class="form-control form-control-sm text-center font-monospace fw-bold border"
                          :class="doseCalc3mg_total > 0 ? 'bg-primary-subtle border-primary-subtle text-primary' : 'bg-light text-muted'"
                          title="รวมขนาดยา 3mg"
                        >
                          {{ doseCalc3mg_total > 0 ? doseCalc3mg_total.toFixed(1) : '-' }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Middle Connector Bracket (Curly Brace) -->
                  <div class="dose-curly-col d-none d-sm-flex align-items-center justify-content-center px-1">
                    <svg width="22" height="74" viewBox="0 0 22 74" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M4 4 C14 4, 13 33, 19 37 C13 41, 14 70, 4 70" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" fill="none" />
                    </svg>
                  </div>

                  <!-- Right Side: รวมทั้งหมด Card -->
                  <div class="total-summary-card text-center p-2 rounded-3 shadow-2xs d-flex flex-column justify-content-center" style="min-width: 105px;">
                    <div class="small fw-bold text-muted text-nowrap" style="font-size: 11px;">รวมทั้งหมด</div>
                    <div class="h5 fw-bolder font-monospace text-primary mb-0 my-1">
                      {{ doseCalcGrandTotal > 0 ? doseCalcGrandTotal.toFixed(1) : '0' }}
                      <small class="fs-7 text-muted fw-normal">มก.</small>
                    </div>
                    <div>
                      <span v-if="doseCalcTotalDays === 7" class="badge bg-success text-white rounded-pill px-2" style="font-size: 10px;">
                        <i class="bi bi-check-circle-fill me-1"></i>ครบ 7 วัน
                      </span>
                      <span v-else-if="doseCalcTotalDays > 0" class="badge bg-warning text-dark rounded-pill px-2" style="font-size: 10px;">
                        {{ doseCalcTotalDays }}/7 วัน
                      </span>
                      <span v-else class="badge bg-secondary-subtle text-muted rounded-pill px-2" style="font-size: 10px;">
                        0 วัน
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Action & Auto-apply Bar -->
                <div class="d-flex flex-wrap align-items-center justify-content-between pt-2 border-top mt-2 gap-2">
                  <button
                    type="button"
                    class="btn btn-xs rounded-pill px-2 py-1 shadow-2xs d-flex align-items-center gap-1"
                    :class="isDoseSynced ? 'btn-success text-white' : 'btn-primary text-white'"
                    @click="applyDoseCalcToCurrent"
                    :disabled="doseCalcGrandTotal === 0"
                    title="นำค่าที่คำนวณได้ไปใส่ในช่องขนาดยาเดิม (Current Dose)"
                  >
                    <i class="bi" :class="isDoseSynced ? 'bi-check2-circle' : 'bi-arrow-down-circle-fill'"></i>
                    <span>{{ isDoseSynced ? 'ซิงค์ลงช่อง B แล้ว (' + doseCalcGrandTotal.toFixed(1) + ' มก.)' : 'กรอกค่าลงช่อง B (' + (doseCalcGrandTotal > 0 ? doseCalcGrandTotal.toFixed(1) : '0') + ' มก.)' }}</span>
                  </button>

                  <div class="form-check form-switch mb-0 d-flex align-items-center gap-1" style="min-height: auto;">
                    <input
                      class="form-check-input mt-0"
                      type="checkbox"
                      role="switch"
                      id="autoApplySwitch"
                      v-model="doseCalcAutoApply"
                      title="อัปเดตค่าลงช่อง B อัตโนมัติทันทีที่พิมพ์"
                    />
                    <label class="form-check-label small text-muted" for="autoApplySwitch" style="font-size: 11px;">
                      อัปเดตลงช่อง B ทันที
                    </label>
                  </div>
                </div>
              </div>

              <!-- Down Pointer to Current Dose Input -->
              <div v-if="showDoseCalculator" class="text-center mb-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small fw-normal d-inline-flex align-items-center gap-1" style="font-size: 11px;">
                  <i class="bi bi-arrow-down-short fs-6"></i>
                  <span>ค่าจะถูกนำมาใส่ตรงขนาดยาเดิมของผู้ป่วย (Current Dose) ด้านล่าง</span>
                </span>
              </div>

              <div class="input-group input-group-lg shadow-xs">
                <span class="input-group-text bg-light text-secondary border-end-0">
                  <i class="bi bi-capsule text-warning"></i>
                </span>
                <input
                  type="number"
                  step="0.5"
                  min="0"
                  class="form-control form-control-lg fw-bold border-start-0 font-monospace text-dark"
                  v-model.number="inputDoseValue"
                  :placeholder="doseUnit === 'weekly' ? 'เช่น 21, 28, 35' : 'เช่น 3, 4, 5'"
                />
                <span class="input-group-text bg-light text-muted">
                  {{ doseUnit === 'weekly' ? 'mg/สัปดาห์' : 'mg/วัน' }}
                </span>
              </div>

              <div class="mt-2 text-muted small d-flex justify-content-between">
                <span>
                  เทียบเท่า:
                  <strong class="text-primary font-monospace">{{ computedCurrentWeeklyDose.toFixed(1) }}</strong>
                  mg/สัปดาห์
                  (วันละ {{ (computedCurrentWeeklyDose / 7).toFixed(2) }} mg)
                </span>
                <span>
                  {{ (computedCurrentWeeklyDose / 3).toFixed(1) }} เม็ด (3mg)/สัปดาห์
                </span>
              </div>
            </div>

            <hr class="my-3 text-muted opacity-25" />

            <!-- Clinical Modifiers & Options -->
            <div class="mb-3">
              <label class="form-label fw-bold text-secondary small text-uppercase mb-2">
                <i class="bi bi-sliders me-1"></i>เงื่อนไขทางคลินิกเพิ่มเติม
              </label>

              <!-- Bleeding Status -->
              <div class="p-3 rounded-3 mb-3 border transition-all" :class="hasMajorBleeding ? 'bg-danger-subtle border-danger' : 'bg-light'">
                <div class="form-check form-switch mb-1">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="bleedingSwitch"
                    v-model="hasMajorBleeding"
                  />
                  <label class="form-check-label fw-bold" :class="hasMajorBleeding ? 'text-danger' : 'text-dark'" for="bleedingSwitch">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-1" v-if="hasMajorBleeding"></i>
                    ผู้ป่วยมีภาวะเลือดออกรุนแรง (Major Bleeding)
                  </label>
                </div>
                <small class="text-muted d-block ps-4">
                  หากมีภาวะเลือดออกรุนแรง ต้องหยุดยา Warfarin ทันที และให้ Vitamin K1 10 mg IV ช้าๆ
                </small>
              </div>

              <!-- First visit off-target -->
              <div class="form-check mb-3">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="firstVisitCheck"
                  v-model="isFirstVisitOffTarget"
                />
                <label class="form-check-label small text-dark" for="firstVisitCheck">
                  ตรวจพบผลเลือดนอก Target ครั้งแรก (อยู่ในช่วง ±0.5 ของเป้าหมาย)
                </label>
                <small class="text-muted d-block ps-4">
                  ตามแนวทาง: หากออกนอก Target ไม่เกิน ±0.5 ครั้งแรก ให้คงขนาดยาเดิม (RM) + นัดติดตาม 1-2 สัปดาห์
                </small>
              </div>

              <!-- Dispensing Duration -->
              <div class="row g-2 align-items-center">
                <div class="col-6">
                  <label class="form-label small text-muted mb-1">จ่ายยาสำหรับ (ระยะเวลา):</label>
                  <select class="form-select form-select-sm rounded-3 shadow-none border" v-model.number="dispenseDays">
                    <option :value="7">1 สัปดาห์ (7 วัน)</option>
                    <option :value="14">2 สัปดาห์ (14 วัน)</option>
                    <option :value="21">3 สัปดาห์ (21 วัน)</option>
                    <option :value="28">4 สัปดาห์ (28 วัน)</option>
                    <option :value="30">1 เดือน (30 วัน)</option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label small text-muted mb-1">ขนาดเม็ดยาหลัก:</label>
                  <select class="form-select form-select-sm rounded-3 shadow-none border" v-model.number="primaryTabletStrength">
                    <option :value="2">Warfarin 2 mg (เม็ดสีส้ม)</option>
                    <option :value="3">Warfarin 3 mg (เม็ดสีฟ้า - แนะนำ)</option>
                    <option :value="5">Warfarin 5 mg (เม็ดสีชมพู)</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Calculation Output & Prescription Plan -->
      <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
          <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-success-subtle text-success rounded-circle p-2">
                <i class="bi bi-check2-circle"></i>
              </span>
              <strong class="text-dark">ผลการคำนวณ & แผนการสั่งจ่ายยา (ค่า C)</strong>
            </div>
            <span class="badge rounded-pill px-3 py-1 fw-bold" :class="evaluation.badgeClass">
              {{ evaluation.nodeLabel }}
            </span>
          </div>

          <div class="card-body p-3 p-md-4">
            <!-- Main Result Banner -->
            <div class="result-banner p-3 p-md-4 rounded-4 mb-4 border shadow-xs" :class="evaluation.bannerBgClass">
              <div class="row g-3 align-items-center">
                <div class="col-12 col-md-7">
                  <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="badge bg-white text-dark shadow-xs fw-bold px-2 py-1">
                      {{ evaluation.formulaLabel }}
                    </span>
                    <strong :class="evaluation.textClass" class="fs-6">
                      {{ evaluation.title }}
                    </strong>
                  </div>
                  <h2 class="fw-bold mb-1 font-monospace" :class="evaluation.textClass">
                    <span v-if="evaluation.newWeeklyDose > 0">
                      {{ evaluation.exactMathDoseFormatted }}
                      <small class="fs-6 fw-normal text-muted">มก. / สัปดาห์</small>
                    </span>
                    <span v-else class="text-danger fs-4">
                      หยุดยาชั่วคราว
                    </span>
                  </h2>
                  <div class="text-secondary small">
                    <span v-if="evaluation.percentChange !== 0">
                      <i class="bi me-1" :class="evaluation.percentChange > 0 ? 'bi-arrow-up-circle-fill text-success' : 'bi-arrow-down-circle-fill text-danger'"></i>
                      ปรับขนาดยา <strong>{{ evaluation.percentText }}</strong> จากขนาดยาเดิม {{ computedCurrentWeeklyDose.toFixed(1) }} mg/สัปดาห์
                    </span>
                    <span v-else>
                      <i class="bi bi-dash-circle-fill text-primary me-1"></i>
                      คงขนาดยาเดิม ไม่มีการปรับเปลี่ยน
                    </span>
                  </div>
                </div>

                <div class="col-12 col-md-5 border-start-md ps-md-3">
                  <div class="p-2 bg-white rounded-3 shadow-xs">
                    <small class="text-muted d-block mb-1 fw-semibold">เฉลี่ยต่อวัน:</small>
                    <div class="fw-bold text-dark fs-5 font-monospace">
                      {{ (evaluation.newWeeklyDose / 7).toFixed(2) }}
                      <small class="fs-7 text-muted">mg/วัน</small>
                    </div>
                    <small class="text-muted d-block mt-1">
                      ประมาณ <strong>{{ (evaluation.newWeeklyDose / (primaryTabletStrength || 3)).toFixed(1) }}</strong> เม็ด ({{ primaryTabletStrength }}mg)/สัปดาห์
                    </small>
                  </div>
                </div>
              </div>

              <!-- Interactive Percentage Selector -->
              <div v-if="baseEvaluation && baseEvaluation.allowAdjustment && computedCurrentWeeklyDose > 0" class="mt-3 pt-3 border-top border-secondary-subtle">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-white text-primary border shadow-xs fw-bold px-2 py-1">
                      <i class="bi bi-percent me-1"></i>เลือก % ปรับขนาดยา
                    </span>
                    <span class="small text-secondary">
                      เกณฑ์ตาม Protocol: <strong class="text-dark">{{ baseEvaluation.protocolRangeText }}</strong>
                    </span>
                  </div>

                  <button
                    v-if="isCustomPercent"
                    type="button"
                    class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1 bg-white shadow-xs"
                    @click="resetPercentToDefault"
                    title="คืนค่าเป็นค่าแนะนำตาม Protocol"
                  >
                    <i class="bi bi-arrow-counterclockwise me-1"></i>คืนค่าแนะนำ ({{ defaultPercentText }})
                  </button>
                </div>

                <div class="d-flex align-items-center flex-wrap gap-2">
                  <!-- Preset Buttons -->
                  <div class="btn-group shadow-xs bg-white rounded-3 p-1 border" role="group">
                    <button
                      v-for="opt in baseEvaluation.percentOptions"
                      :key="opt.value"
                      type="button"
                      class="btn btn-sm rounded-2 px-3 fw-bold transition-all"
                      :class="currentPercent === opt.value ? 'btn-primary shadow-xs' : 'btn-light text-dark'"
                      @click="setPercent(opt.value)"
                    >
                      {{ opt.label }}
                      <span v-if="opt.isDefault" class="badge ms-1" :class="currentPercent === opt.value ? 'bg-white text-primary' : 'bg-primary-subtle text-primary'">
                        แนะนำ
                      </span>
                    </button>
                  </div>

                  <!-- Stepper / Custom Number Input -->
                  <div class="input-group input-group-sm shadow-xs bg-white rounded-3 border" style="width: 175px;">
                    <button class="btn btn-light px-2 text-secondary" type="button" @click="stepPercent(-0.5)" title="ลด 0.5%">
                      <i class="bi bi-dash"></i>
                    </button>
                    <input
                      type="number"
                      step="0.5"
                      class="form-control text-center fw-bold font-monospace border-0 bg-transparent"
                      :value="currentPercent"
                      @input="onCustomPercentInput($event.target.value)"
                      placeholder="%"
                    />
                    <span class="input-group-text bg-transparent border-0 px-1 fw-bold text-muted">%</span>
                    <button class="btn btn-light px-2 text-secondary" type="button" @click="stepPercent(0.5)" title="เพิ่ม 0.5%">
                      <i class="bi bi-plus"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Mathematical Formula Calculation Card (ตรงตามสูตรที่กำหนด) -->
            <div v-if="computedCurrentWeeklyDose > 0 && evaluation.newWeeklyDose > 0" class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white">
              <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-primary-subtle text-primary rounded-circle p-2">
                    <i class="bi bi-calculator"></i>
                  </span>
                  <strong class="text-dark">สูตรการคำนวณตามคณิตศาสตร์</strong>
                </div>
                <span class="badge bg-light text-muted border font-monospace">ขนาดยาใหม่ = B &times; (1 + C/100)</span>
              </div>

              <div class="card-body p-3 p-md-4">
                <!-- Mathematical Formula & Step-by-Step Substitution matching user prompt -->
                <div class="p-3 mb-3 rounded-3 bg-light border text-center">
                  <div class="text-muted small mb-1 fw-bold">สูตรที่ใช้คำนวณ:</div>
                  <div class="fs-5 fw-bold text-dark font-monospace mb-2">
                    ขนาดยาใหม่ = B &times; (1 + <span class="text-primary">C</span>/100)
                  </div>
                  <div class="text-muted small mb-1 fw-bold">แทนค่า:</div>
                  <div class="fs-6 font-monospace text-secondary">
                    = {{ computedCurrentWeeklyDose }} &times; (1 + <span :class="evaluation.percentChange >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold'">{{ evaluation.percentChange >= 0 ? '+' + evaluation.percentChange : evaluation.percentChange }}</span>/100)
                  </div>
                  <div class="fs-5 font-monospace mt-2 text-dark">
                    = {{ computedCurrentWeeklyDose }} &times; {{ evaluation.factorFormatted }} = <strong class="text-success border-bottom border-success border-2 pb-1">{{ evaluation.exactMathDoseFormatted }} มก./สัปดาห์</strong>
                  </div>
                </div>

                <!-- Summary Breakdown Table (Exact match to User Screenshot) -->
                <div class="border rounded-3 p-3 bg-white">
                  <div class="small fw-bold text-muted mb-2 pb-1 border-bottom">
                    ผลการคำนวณตัวอย่าง
                  </div>
                  <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-secondary">ขนาดยาเดิม</span>
                    <span class="fw-bold text-dark font-monospace fs-6">{{ computedCurrentWeeklyDose }} มก./สัปดาห์</span>
                  </div>
                  <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-secondary">เปอร์เซ็นต์ที่เลือก</span>
                    <span class="fw-bold font-monospace fs-6" :class="evaluation.percentChange >= 0 ? 'text-success' : 'text-danger'">
                      {{ evaluation.percentText }}
                    </span>
                  </div>
                  <div class="d-flex justify-content-between align-items-center pt-2">
                    <span class="fw-bold text-dark">ขนาดยาใหม่ตามคณิตศาสตร์</span>
                    <span class="fw-bold fs-5 font-monospace text-success">
                      {{ evaluation.exactMathDoseFormatted }} มก./สัปดาห์
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Clinical Recommendations Box -->
            <div class="card border rounded-3 mb-4 bg-light">
              <div class="card-body p-3">
                <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                  <i class="bi bi-clipboard2-pulse-fill text-primary"></i>
                  <span>แนวทางปฏิบัติทางคลินิก (Clinical Action Plan)</span>
                </h6>
                <div class="row g-2 small">
                  <!-- Action 1: Omit dose -->
                  <div class="col-12 col-sm-6" v-if="evaluation.omitDoseText">
                    <div class="p-2 bg-white rounded-2 border">
                      <span class="text-muted d-block">การงดยา (Omit Dose):</span>
                      <strong class="text-danger">
                        <i class="bi bi-x-circle me-1"></i>{{ evaluation.omitDoseText }}
                      </strong>
                    </div>
                  </div>

                  <!-- Action 2: Vitamin K1 -->
                  <div class="col-12 col-sm-6" v-if="evaluation.vitKText">
                    <div class="p-2 bg-white rounded-2 border">
                      <span class="text-muted d-block">การให้ Vitamin K1:</span>
                      <strong class="text-danger">
                        <i class="bi bi-capsule me-1"></i>{{ evaluation.vitKText }}
                      </strong>
                    </div>
                  </div>

                  <!-- Action 3: Recommendation -->
                  <div class="col-12">
                    <div class="p-2 bg-white rounded-2 border">
                      <span class="text-muted d-block">คำแนะนำการปรับยา:</span>
                      <strong class="text-dark">{{ evaluation.actionRecommendation }}</strong>
                    </div>
                  </div>

                  <!-- Action 4: Follow up -->
                  <div class="col-12">
                    <div class="p-2 bg-white rounded-2 border">
                      <span class="text-muted d-block">ระยะเวลานัดตรวจติดตาม (Follow-up):</span>
                      <strong class="text-primary">
                        <i class="bi bi-calendar-event me-1"></i>{{ evaluation.followUpText }}
                      </strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Daily Dispensing Pill Schedule (จันทร์ - อาทิตย์) -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                  <i class="bi bi-calendar3-week text-primary"></i>
                  <span>ตารางจัดเม็ดยารายวัน (Daily Schedule: จันทร์ - อาทิตย์)</span>
                </label>
                <span class="badge bg-light text-muted border small">
                  เป้าหมายตามสูตร: {{ evaluation.exactMathDoseFormatted }} มก./สัปดาห์
                  <span v-if="scheduleWeeklyTotal > 0 && Math.abs(scheduleWeeklyTotal - evaluation.newWeeklyDose) >= 0.05" class="text-primary ms-1">
                    (จัดเม็ดยารวมได้จริง: {{ scheduleWeeklyTotal }} มก.)
                  </span>
                </span>
              </div>

              <!-- 7 Days Grid -->
              <div class="row g-2 text-center">
                <div
                  v-for="(day, idx) in dailySchedule"
                  :key="idx"
                  class="col"
                >
                  <div
                    class="p-2 rounded-3 border h-100 d-flex flex-column justify-content-between transition-all"
                    :class="day.isOmit ? 'bg-danger-subtle border-danger' : 'bg-white shadow-xs'"
                  >
                    <div>
                      <div class="fw-bold small mb-1" :class="day.isOmit ? 'text-danger' : 'text-secondary'">
                        {{ day.name }}
                      </div>
                      <div v-if="day.isOmit" class="badge bg-danger text-white my-1 small">
                        งดยา
                      </div>
                      <div v-else class="my-1">
                        <div class="pill-visual-circle mx-auto mb-1" :class="getPillClass(day.dose)">
                          {{ day.dose }}
                        </div>
                        <strong class="font-monospace text-dark fs-6">{{ day.dose }}</strong>
                        <small class="text-muted d-block" style="font-size: 10px;">mg</small>
                      </div>
                    </div>
                    <div class="mt-1 pt-1 border-top small text-muted" style="font-size: 11px;">
                      {{ day.pillsDescription }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Dispensing Summary (จำนวนเม็ดที่ต้องจ่าย) -->
            <div class="card border rounded-3 p-3 bg-white shadow-xs">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="text-dark d-flex align-items-center gap-2">
                  <i class="bi bi-box-seam-fill text-success"></i>
                  <span>สรุปจำนวนเม็ดยาที่ต้องจ่าย (Dispense Quantity สำหรับ {{ dispenseDays }} วัน)</span>
                </strong>
                <div class="d-flex align-items-center gap-2">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-success rounded-pill px-3 d-flex align-items-center gap-1"
                    @click="openPrintModal()"
                    title="พิมพ์ตารางการรับประทานยา (WF SmartDose)"
                  >
                    <i class="bi bi-printer-fill"></i>
                    <span>พิมพ์ตารางยา</span>
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary rounded-pill px-3 d-flex align-items-center gap-1"
                    @click="copyPrescriptionText"
                  >
                    <i class="bi bi-clipboard"></i>
                    <span>คัดลอกวิธีใช้ยา</span>
                  </button>
                </div>
              </div>

              <div class="row g-3 align-items-center">
                <div class="col-12 col-md-8">
                  <div class="d-flex gap-2 flex-wrap">
                    <div
                      v-for="pill in dispensePillBreakdown"
                      :key="pill.strength"
                      class="p-2 px-3 bg-light rounded-3 border d-flex align-items-center gap-2"
                    >
                      <div class="pill-visual-circle" :class="getPillClass(pill.strength)">
                        {{ pill.strength }}
                      </div>
                      <div>
                        <div class="small fw-bold text-dark">Warfarin {{ pill.strength }} mg</div>
                        <div class="text-success fw-bold font-monospace fs-6">
                          {{ pill.count }} <small class="text-muted fw-normal">เม็ด</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-4 text-md-end">
                  <div class="text-muted small">รวมจ่ายยาทั้งหมด:</div>
                  <h4 class="fw-bold text-dark mb-0 font-monospace">
                    {{ totalDispensedPills }} <span class="fs-6 fw-normal text-muted">เม็ด</span>
                  </h4>
                </div>
              </div>

              <!-- Prescription Text Preview -->
              <div class="mt-3 p-2 bg-light rounded-2 font-monospace small border text-secondary position-relative">
                <span class="text-muted d-block mb-1" style="font-size: 11px;">ข้อความวิธีใช้ยา (Sig):</span>
                {{ prescriptionText }}
              </div>
            </div>

            <!-- Save Calculation Card -->
            <div class="card border border-success-subtle bg-success-subtle bg-opacity-10 rounded-4 p-3 mt-3 shadow-xs">
              <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success text-white rounded-circle p-1">
                      <i class="bi bi-floppy-fill"></i>
                    </span>
                    <strong class="text-dark">บันทึกประวัติการคำนวณ (Save History)</strong>
                  </div>
                  <div class="small text-muted">
                    บันทึกข้อมูล HN, ค่า INR, ขนาดยาเดิม-ใหม่ และตารางจัดยาลงฐานข้อมูล
                  </div>
                  <div v-if="lastSavedTime" class="text-success small mt-1 fw-bold">
                    <i class="bi bi-check-circle-fill me-1"></i>บันทึกล่าสุดเมื่อ {{ lastSavedTime }} (รหัสบันทึก #{{ lastSavedId }})
                  </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0 flex-wrap">
                  <button
                    type="button"
                    class="btn btn-outline-primary rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-1 bg-white shadow-xs"
                    @click="openPrintModal()"
                    title="พิมพ์ตารางการรับประทานยา (WF SmartDose)"
                  >
                    <i class="bi bi-printer-fill text-primary"></i>
                    <span>พิมพ์ตารางยา</span>
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-1 bg-white shadow-xs"
                    @click="openHistoryModal"
                  >
                    <i class="bi bi-clock-history"></i>
                    <span>ดูประวัติ</span>
                    <span v-if="historyTotalCount > 0" class="badge bg-secondary text-white ms-1">{{ historyTotalCount }}</span>
                  </button>
                  <button
                    type="button"
                    class="btn btn-success btn-lg rounded-pill px-4 py-2 fw-bold d-flex align-items-center gap-2 shadow-sm text-white"
                    :disabled="saving"
                    @click="saveCalculation"
                  >
                    <span v-if="saving" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <i v-else class="bi bi-floppy2-fill"></i>
                    <span>{{ saving ? 'กำลังบันทึก...' : 'บันทึกประวัติ (Save)' }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Interactive Protocol Flowchart Diagram (ตามที่ผู้ใช้วาดในกระดาษเป๊ะๆ) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
      <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-warning-subtle text-warning-emphasis rounded-circle p-2">
            <i class="bi bi-diagram-3-fill"></i>
          </span>
          <div>
            <strong class="text-dark">แผนภาพแนวทางการปรับขนาดยา Warfarin ตามค่า INR (Flowchart Protocol)</strong>
            <small class="text-muted d-block">กล่องเกณฑ์ที่ตรงกับผลเลือดของผู้ป่วย (ค่า A) จะสว่างขึ้นอัตโนมัติ</small>
          </div>
        </div>
        <div class="text-muted small">
          ค่า A ปัจจุบัน: <strong class="text-primary font-monospace">{{ inrValue || '-' }}</strong>
        </div>
      </div>

      <div class="card-body p-3 p-md-4 bg-light overflow-x-auto">
        <div class="flowchart-container mx-auto py-2" style="min-width: 820px; max-width: 960px;">
          <!-- Top Branch: 2.0 - 3.0 (Target -> RM -> B) -->
          <div class="d-flex justify-content-center mb-3">
            <div
              class="flow-node target-node border p-3 rounded-4 bg-white shadow-xs text-center transition-all"
              :class="{ 'node-active': evaluation.nodeKey === 'RM' }"
              style="width: 320px;"
            >
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="badge bg-success text-white rounded-pill px-2 py-1 small fw-bold">Target Range</span>
                <span class="fw-bold text-success font-monospace fs-5">2.0 - 3.0</span>
              </div>
              <div class="d-flex align-items-center justify-content-center gap-2 my-1">
                <i class="bi bi-arrow-up text-success fs-5"></i>
                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-1 fw-bold fs-6">
                  RM (คงขนาดยาเดิม)
                </span>
                <i class="bi bi-arrow-right text-success fs-5"></i>
                <span class="circle-badge circle-b">B</span>
              </div>
              <small class="text-muted d-block" style="font-size: 11px;">
                มา ± 0.2 ให้ RM | มา ± 0.5 ครั้งแรก ให้ RM + f/u 1-2 สัปดาห์
              </small>
            </div>
          </div>

          <!-- Middle Layer: Left (Supratherapeutic) | Center (Inputs A + B) | Right (Subtherapeutic) -->
          <div class="row g-3 align-items-center mb-3">
            <!-- Left Column: Supratherapeutic (INR สูงกว่าเป้าหมาย) -->
            <div class="col-4 d-flex flex-column gap-3">
              <!-- Node E: 3.1 - 3.9 -->
              <div
                class="flow-node border p-2 rounded-3 bg-white shadow-xs transition-all d-flex align-items-center justify-content-between"
                :class="{ 'node-active': evaluation.nodeKey === 'E' }"
              >
                <div>
                  <span class="badge bg-warning text-dark font-monospace fw-bold mb-1">3.1 - 3.9</span>
                  <div class="small fw-semibold text-secondary">
                    <i class="bi bi-arrow-down text-danger me-1"></i>ลด 5 - 10% &times; B
                  </div>
                </div>
                <div class="circle-badge circle-e">E</div>
              </div>

              <!-- Node F: 4.0 - 4.9 -->
              <div
                class="flow-node border p-2 rounded-3 bg-white shadow-xs transition-all d-flex align-items-center justify-content-between"
                :class="{ 'node-active': evaluation.nodeKey === 'F' }"
              >
                <div>
                  <span class="badge bg-warning-subtle text-warning-emphasis border border-warning font-monospace fw-bold mb-1">4.0 - 4.9</span>
                  <div class="small fw-semibold text-danger">
                    <i class="bi bi-arrow-down text-danger me-1"></i>ลด 10% &times; B
                    <span class="badge bg-danger-subtle text-danger rounded-pill ms-1">+ Omit 1 dose</span>
                  </div>
                </div>
                <div class="circle-badge circle-f">F</div>
              </div>

              <!-- Node G: 5.0 - 8.9 -->
              <div
                class="flow-node border p-2 rounded-3 bg-white shadow-xs transition-all d-flex align-items-center justify-content-between"
                :class="{ 'node-active': evaluation.nodeKey === 'G' || evaluation.nodeKey === 'G_BLEED' }"
              >
                <div>
                  <span class="badge bg-danger text-white font-monospace fw-bold mb-1">5.0 - 8.9</span>
                  <div class="small fw-semibold text-danger">
                    Omit 1-2 dose + Vit K1 oral, ลด 20%
                  </div>
                  <div class="text-danger fw-bold" style="font-size: 11px;">
                    *ถ้ามี Major Bleeding ให้ Vit K 10 mg IV
                  </div>
                </div>
                <div class="circle-badge circle-g">G</div>
              </div>
            </div>

            <!-- Center Column: Circle A & B Hub -->
            <div class="col-4 text-center">
              <div class="center-hub-card border p-3 rounded-circle mx-auto bg-white shadow-sm d-flex flex-column align-items-center justify-content-center">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <div class="circle-badge circle-a">A</div>
                  <span class="small text-muted fw-bold">ผลเลือด INR</span>
                </div>
                <div class="display-6 fw-bold font-monospace text-primary my-1">
                  {{ inrValue || '-' }}
                </div>
                <hr class="w-75 my-1" />
                <div class="d-flex align-items-center gap-2 mt-1">
                  <div class="circle-badge circle-b">B</div>
                  <span class="small text-muted fw-bold">ยาเดิม: {{ computedCurrentWeeklyDose.toFixed(1) }} mg</span>
                </div>
              </div>
            </div>

            <!-- Right Column: Subtherapeutic (INR ต่ำกว่าเป้าหมาย) -->
            <div class="col-4 d-flex flex-column gap-3">
              <!-- Node C: < 1.5 -->
              <div
                class="flow-node border p-2 rounded-3 bg-white shadow-xs transition-all d-flex align-items-center justify-content-between"
                :class="{ 'node-active': evaluation.nodeKey === 'C' }"
              >
                <div class="circle-badge circle-c">C</div>
                <div class="text-end">
                  <span class="badge bg-info text-white font-monospace fw-bold mb-1">&lt; 1.5</span>
                  <div class="small fw-semibold text-success">
                    <i class="bi bi-arrow-up text-success me-1"></i>+ 10 - 20% &times; B
                  </div>
                </div>
              </div>

              <!-- Node D: 1.5 - 1.9 -->
              <div
                class="flow-node border p-2 rounded-3 bg-white shadow-xs transition-all d-flex align-items-center justify-content-between"
                :class="{ 'node-active': evaluation.nodeKey === 'D' }"
              >
                <div class="circle-badge circle-d">D</div>
                <div class="text-end">
                  <span class="badge bg-info-subtle text-info-emphasis border border-info font-monospace fw-bold mb-1">1.5 - 1.9</span>
                  <div class="small fw-semibold text-success">
                    <i class="bi bi-arrow-up text-success me-1"></i>+ 5 - 10% &times; B
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Branch: >= 9.0 -->
          <div class="d-flex justify-content-center">
            <div
              class="flow-node border p-3 rounded-4 bg-white shadow-xs text-center transition-all"
              :class="{ 'node-active': evaluation.nodeKey === 'GT9' || evaluation.nodeKey === 'GT9_BLEED' }"
              style="width: 480px;"
            >
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="badge bg-danger text-white rounded-pill px-3 py-1 fw-bold">High Risk</span>
                <span class="fw-bold text-danger font-monospace fs-5">&ge; 9.0</span>
              </div>
              <div class="small fw-bold text-dark my-1">
                <span class="text-danger">No Bleeding:</span> Vit K 5 - 10 mg oral, ปรับลดขนาดยา &gt; 20%
              </div>
              <div class="small text-danger fw-bold">
                *Major Bleeding: หยุดยา Warfarin ทันที + Vit K 10 mg IV ช้าๆ + FFP
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Calculation History Modal -->
    <div v-if="showHistoryModal" class="history-modal-overlay" @click.self="closeHistoryModal">
      <div class="history-modal-container">
        <!-- Modal Header -->
        <div class="d-flex align-items-center justify-content-between p-3 px-4 border-bottom bg-light">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-circle p-2 text-white">
              <i class="bi bi-clock-history fs-6"></i>
            </span>
            <div>
              <h5 class="fw-bold mb-0 text-dark">ประวัติการคำนวณปรับขนาดยา Warfarin</h5>
              <small class="text-muted">รายการประวัติที่เคยบันทึกไว้ในระบบ สามารถค้นหา ดึงข้อมูลกลับมาคำนวณซ้ำ หรือส่งออก Excel ได้</small>
            </div>
          </div>
          <button type="button" class="btn btn-close" @click="closeHistoryModal" aria-label="Close"></button>
        </div>

        <!-- Filter & Actions Bar -->
        <div class="p-3 px-4 border-bottom bg-white">
          <div class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted border-end-0">
                  <i class="bi bi-search"></i>
                </span>
                <input
                  type="text"
                  class="form-control form-control-sm border-start-0"
                  placeholder="ค้นหา HN, ชื่อผู้ป่วย, หมายเหตุ, ผู้บันทึก..."
                  v-model="historySearch"
                  @keyup.enter="fetchHistory"
                />
              </div>
            </div>

            <div class="col-6 col-md-2">
              <input
                type="date"
                class="form-control form-control-sm"
                v-model="historyStartDate"
                @change="fetchHistory"
                title="ตั้งแต่วันที่"
              />
            </div>

            <div class="col-6 col-md-2">
              <input
                type="date"
                class="form-control form-control-sm"
                v-model="historyEndDate"
                @change="fetchHistory"
                title="ถึงวันที่"
              />
            </div>

            <div class="col-12 col-md-4 d-flex justify-content-md-end gap-2 flex-wrap">
              <button class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" @click="fetchHistory" :disabled="loadingHistory">
                <span v-if="loadingHistory" class="spinner-border spinner-border-sm me-1" role="status"></span>
                <i v-else class="bi bi-arrow-clockwise me-1"></i>รีเฟรช
              </button>
              <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" @click="clearHistoryFilter">
                ล้างค้นหา
              </button>
              <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold text-white shadow-xs" @click="exportHistoryExcel" :disabled="historyList.length === 0">
                <i class="bi bi-file-earmark-excel me-1"></i>ส่งออก Excel
              </button>
            </div>
          </div>
        </div>

        <!-- Modal Body (Table) -->
        <div class="history-modal-body p-3 px-4 overflow-y-auto flex-grow-1">
          <div v-if="loadingHistory" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="text-muted small mt-2">กำลังโหลดประวัติการคำนวณ...</div>
          </div>

          <div v-else-if="historyList.length === 0" class="text-center py-5">
            <i class="bi bi-folder-x fs-1 text-muted opacity-50 d-block mb-2"></i>
            <h6 class="text-secondary fw-bold">ไม่พบประวัติการคำนวณ</h6>
            <small class="text-muted">เมื่อกดปุ่ม "บันทึกประวัติ (Save)" ในหน้าคำนวณ รายการจะมาแสดงที่นี่</small>
          </div>

          <div v-else class="table-responsive">
            <table class="table table-hover table-striped align-middle small mb-0">
              <thead class="table-light text-secondary sticky-top">
                <tr>
                  <th style="width: 140px;">วันที่-เวลา</th>
                  <th style="width: 160px;">ผู้ป่วย</th>
                  <th style="width: 110px;">INR (A)</th>
                  <th style="width: 110px;">ยาเดิม (B)</th>
                  <th style="width: 90px;">% ปรับ (C)</th>
                  <th style="width: 130px;">ขนาดยาใหม่</th>
                  <th style="width: 110px;">เฉลี่ย/วัน</th>
                  <th>ผู้บันทึก & หมายเหตุ</th>
                  <th style="width: 140px;" class="text-center">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in historyList" :key="item.id">
                  <td>
                    <div class="fw-bold text-dark">{{ formatDateTime(item.created_at) }}</div>
                    <small class="text-muted font-monospace">#{{ item.id }}</small>
                  </td>
                  <td>
                    <div class="fw-bold text-primary font-monospace">
                      {{ item.hn || '-' }}
                    </div>
                    <div class="text-truncate" style="max-width: 160px;" :title="item.patient_name">
                      {{ item.patient_name || '(ไม่ระบุชื่อ)' }}
                    </div>
                  </td>
                  <td>
                    <span class="badge px-2 py-1 font-monospace" :class="getInrBadgeClass(item.inr_value)">
                      {{ item.inr_value }}
                    </span>
                    <small class="d-block text-muted text-truncate" style="max-width: 100px;">
                      {{ item.formula_label }}
                    </small>
                  </td>
                  <td>
                    <strong class="font-monospace text-dark">{{ item.prev_weekly_dose }}</strong>
                    <small class="text-muted d-block">มก./สัปดาห์</small>
                  </td>
                  <td>
                    <span
                      class="badge rounded-pill px-2 py-1 font-monospace"
                      :class="Number(item.percent_change) > 0 ? 'bg-success text-white' : (Number(item.percent_change) < 0 ? 'bg-danger text-white' : 'bg-secondary text-white')"
                    >
                      {{ Number(item.percent_change) > 0 ? '+' + item.percent_change : item.percent_change }}%
                    </span>
                  </td>
                  <td>
                    <strong class="text-success font-monospace fs-6">{{ item.new_weekly_dose }}</strong>
                    <small class="text-muted d-block">มก./สัปดาห์</small>
                  </td>
                  <td>
                    <span class="font-monospace fw-bold">{{ item.daily_avg_dose }}</span>
                    <small class="text-muted d-block">มก./วัน</small>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-1">
                      <i class="bi bi-person text-muted"></i>
                      <strong class="text-secondary">{{ item.created_by || '-' }}</strong>
                    </div>
                    <small v-if="item.note" class="text-muted d-block text-truncate" style="max-width: 220px;" :title="item.note">
                      {{ item.note }}
                    </small>
                  </td>
                  <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                      <button
                        type="button"
                        class="btn btn-outline-success"
                        @click="printPastRecord(item)"
                        title="พิมพ์ตารางการรับประทานยา (WF SmartDose)"
                      >
                        <i class="bi bi-printer-fill"></i>
                      </button>
                      <button
                        type="button"
                        class="btn btn-outline-primary"
                        @click="loadRecordIntoCalculator(item)"
                        title="ดึงข้อมูลนี้กลับมาหน้าคำนวณ"
                      >
                        <i class="bi bi-arrow-repeat me-1"></i>ดึงค่า
                      </button>
                      <button
                        type="button"
                        class="btn btn-outline-danger"
                        @click="confirmDeleteRecord(item)"
                        title="ลบรายการนี้"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-3 px-4 border-top bg-light d-flex justify-content-between align-items-center">
          <small class="text-muted">
            แสดงทั้งหมด <strong>{{ historyList.length }}</strong> รายการ
          </small>
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" @click="closeHistoryModal">
            ปิดหน้าต่าง
          </button>
        </div>
      </div>
    </div>

    <!-- WF SmartDose Print Modal (แบบตารางการรับประทานยาตามรูป) -->
    <div v-if="showPrintModal" class="wf-print-modal-overlay" @click.self="closePrintModal">
      <div class="wf-print-modal-dialog">
        <!-- Modal Top Bar (Non-printable controls) -->
        <div class="wf-print-modal-header p-3 px-4 border-bottom bg-white d-flex align-items-center justify-content-between gap-3 no-print">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary rounded-circle p-2">
              <i class="bi bi-printer-fill fs-5"></i>
            </span>
            <div>
              <h5 class="fw-bold mb-0 text-dark">พิมพ์ตารางการรับประทานยา (WF SmartDose)</h5>
              <small class="text-muted">เอกสารแนะนำขนาดยาและการรับประทานยา Warfarin สำหรับผู้ป่วย</small>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-light text-dark border px-3 py-2 fw-semibold d-flex align-items-center gap-1">
              <i class="bi bi-hospital text-primary"></i>
              <span>กลุ่มงานเภสัชกรรม โรงพยาบาลชานุมาน</span>
            </span>

            <button
              type="button"
              class="btn btn-primary rounded-pill px-4 py-2 fw-bold d-flex align-items-center gap-2 shadow-sm text-white"
              @click="triggerBrowserPrint"
            >
              <i class="bi bi-printer-fill"></i>
              <span>สั่งพิมพ์ (Print)</span>
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary rounded-pill px-3 py-2"
              @click="closePrintModal"
            >
              ปิด
            </button>
          </div>
        </div>

        <!-- Modal Body (Printable Sheet) -->
        <div class="wf-print-modal-body p-3 p-md-4 overflow-y-auto">
          <!-- The EXACT Sheet Layout Matching Photo -->
          <div class="wf-smartdose-sheet mx-auto bg-white shadow-sm">
            <!-- Header Title -->
            <div class="text-center mb-1">
              <h3 class="wf-sheet-title fw-bold text-dark mb-1">WF SmartDose: ตารางการรับประทานยา</h3>
              <h5 class="wf-sheet-hospital text-secondary fw-semibold mb-2">กลุ่มงานเภสัชกรรม โรงพยาบาลชานุมาน</h5>
            </div>

            <!-- Dose & Info Line -->
            <div class="wf-sheet-dose-line text-center mb-3">
              <div class="wf-dose-meta">
                เดิม: <strong>{{ activePrintData.prevWeeklyDose.toFixed(1) }} mg/wk</strong>
                &nbsp;&nbsp;&nbsp;&nbsp;
                ใหม่: <strong>{{ activePrintData.newWeeklyDose.toFixed(1) }} mg/wk</strong>
                &nbsp;&nbsp;&nbsp;&nbsp;
                ปรับ: <strong>{{ printPercentChangeText }}</strong>
              </div>
              <div v-if="activePrintData.hn || activePrintData.patientName" class="wf-patient-meta small text-muted mt-1">
                <span v-if="activePrintData.hn">HN: <strong>{{ activePrintData.hn }}</strong>&nbsp;&nbsp;&nbsp;&nbsp;</span>
                <span v-if="activePrintData.patientName">ชื่อ-สกุล: <strong>{{ activePrintData.patientName }}</strong>&nbsp;&nbsp;&nbsp;&nbsp;</span>
                <span v-if="printDateFormatted">วันที่: <strong>{{ printDateFormatted }}</strong></span>
              </div>
            </div>

            <!-- 7-Day Table Container -->
            <div class="wf-schedule-table border rounded-3 mb-3">
              <div
                v-for="(row, rIdx) in printScheduleRows"
                :key="rIdx"
                class="wf-schedule-row"
                :class="{ 'wf-row-omit': row.isOmit }"
              >
                <!-- Day Badge -->
                <div class="wf-col-day">
                  <span class="wf-day-badge">{{ row.dayName }}</span>
                </div>

                <!-- Middle: Pills with dots -->
                <div class="wf-col-pills">
                  <div v-if="row.isOmit" class="wf-pill-omit text-danger fw-bold">
                    งดยา 1 มื้อ
                  </div>
                  <div v-else class="wf-pills-list">
                    <div
                      v-for="(pill, pIdx) in row.pills"
                      :key="pIdx"
                      class="wf-pill-item d-flex align-items-center"
                    >
                      <span class="wf-pill-text">Warfarin {{ pill.strength }} mg ({{ pill.colorName }}) {{ pill.qtyText }}</span>
                      <span class="wf-pill-circle" :class="pill.circleClass"></span>
                    </div>
                  </div>
                </div>

                <!-- Right: Timing Badge -->
                <div class="wf-col-timing text-end">
                  <span class="wf-timing-badge">ก่อนนอน</span>
                </div>
              </div>
            </div>

            <!-- Instruction Box below Table -->
            <div class="wf-instruction-box p-3 rounded-3 mb-3">
              <div
                v-for="(line, lIdx) in printInstructions"
                :key="lIdx"
                class="wf-inst-line"
              >
                {{ line }}
              </div>
            </div>

            <!-- Footer Warning -->
            <div class="wf-footer-warning text-center">
              <small class="text-secondary fst-italic">
                *อย่าหยุดยาเองโดยไม่ปรึกษาแพทย์หรือเภสัชกร หากมีอาการเลือดออกผิดปกติให้รีบพบแพทย์
              </small>
            </div>
          </div>
        </div>

        <!-- Modal Footer Controls (Non-printable) -->
        <div class="wf-print-modal-footer p-3 px-4 border-top bg-light d-flex justify-content-between align-items-center no-print">
          <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>ขนาดกระดาษแนะนำ: A4 แนวตั้ง (ระบบจะจัดหน้าพิมพ์เฉพาะใบตารางยาให้อัตโนมัติ)
          </small>
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" @click="closePrintModal">
              ปิด
            </button>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold text-white shadow-xs" @click="triggerBrowserPrint">
              <i class="bi bi-printer me-1"></i>สั่งพิมพ์ (Print)
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'WarfarinCalculator',
  props: {
    initialInr: {
      type: Number,
      default: null
    },
    initialDose: {
      type: Number,
      default: 21
    }
  },
  data() {
    return {
      // Patient & Recording Info
      patientHn: '',
      patientName: '',
      recordedBy: localStorage.getItem('warfarin_calc_user') || '',
      calculationNote: '',
      loadingPatient: false,
      saving: false,
      lastSavedTime: null,
      lastSavedId: null,

      // Print Modal State (WF SmartDose Sheet)
      showPrintModal: false,
      hospitalName: 'กลุ่มงานเภสัชกรรม โรงพยาบาลชานุมาน',
      printRecordData: null,

      // History Modal State
      showHistoryModal: false,
      historyList: [],
      historyTotalCount: 0,
      loadingHistory: false,
      historySearch: '',
      historyStartDate: '',
      historyEndDate: '',

      // Calculator State
      inrValue: this.initialInr !== null ? this.initialInr : 2.5,
      inputDoseValue: this.initialDose || 21,
      doseUnit: 'weekly', // 'weekly' (mg/week) or 'daily' (mg/day)
      targetMin: 2.0,
      targetMax: 3.0,
      hasMajorBleeding: false,
      isFirstVisitOffTarget: false,
      dispenseDays: 14,
      primaryTabletStrength: 3, // 2, 3, or 5 mg
      manualPercent: null, // User selected or typed percentage override
      inrPresets: [
        { label: '< 1.5 (1.3)', val: 1.3 },
        { label: '1.5-1.9 (1.7)', val: 1.7 },
        { label: 'Target (2.5)', val: 2.5 },
        { label: '3.1-3.9 (3.4)', val: 3.4 },
        { label: '4.0-4.9 (4.3)', val: 4.3 },
        { label: '5.0-8.9 (6.5)', val: 6.5 },
        { label: '≥ 9.0 (10.0)', val: 10.0 }
      ],

      // Dose Calculator Helper (คำนวณจากขนาดเม็ดยา * จำนวนวัน รวม 2 ขนาด)
      showDoseCalculator: true,
      doseCalc2mg_dose: 2,
      doseCalc2mg_days: null,
      doseCalc3mg_dose: 3,
      doseCalc3mg_days: null,
      doseCalcAutoApply: true
    };
  },
  mounted() {
    this.fetchHistory();
  },
  watch: {
    'baseEvaluation.nodeKey'(newKey, oldKey) {
      if (newKey !== oldKey) {
        this.manualPercent = null;
      }
    }
  },
  computed: {
    // คำนวณขนาดยา 2mg: ขนาดเม็ดยา * จำนวนวัน
    doseCalc2mg_total() {
      const dose = Number(this.doseCalc2mg_dose) || 0;
      const days = Number(this.doseCalc2mg_days) || 0;
      return dose * days;
    },

    // คำนวณขนาดยา 3mg: ขนาดเม็ดยา * จำนวนวัน
    doseCalc3mg_total() {
      const dose = Number(this.doseCalc3mg_dose) || 0;
      const days = Number(this.doseCalc3mg_days) || 0;
      return dose * days;
    },

    // รวมจำนวนวันทั้งหมดของทั้ง 2 ขนาด
    doseCalcTotalDays() {
      const d1 = Number(this.doseCalc2mg_days) || 0;
      const d2 = Number(this.doseCalc3mg_days) || 0;
      return d1 + d2;
    },

    // รวมขนาดทั้ง 2 ขนาดเข้าด้วยกัน
    doseCalcGrandTotal() {
      return this.doseCalc2mg_total + this.doseCalc3mg_total;
    },

    // ตรวจสอบว่าค่าใน B ซิงค์ตรงกับผลรวมของเครื่องมือคำนวณหรือไม่
    isDoseSynced() {
      if (this.doseCalcTotalDays === 0 || this.doseCalcGrandTotal === 0) return false;
      return Math.abs(this.computedCurrentWeeklyDose - this.doseCalcGrandTotal) < 0.05;
    },

    computedCurrentWeeklyDose() {
      const val = Number(this.inputDoseValue) || 0;
      if (this.doseUnit === 'daily') {
        return val * 7;
      }
      return val;
    },

    inrStatusBadge() {
      const inr = Number(this.inrValue);
      if (!inr || inr <= 0) return null;
      if (inr < 1.5) return { text: 'ต่ำกว่าเป้าหมายมาก', class: 'bg-info text-white' };
      if (inr <= 1.9) return { text: 'ต่ำกว่าเป้าหมาย', class: 'bg-info-subtle text-info-emphasis' };
      if (inr <= 3.0) return { text: 'อยู่ในเกณฑ์เป้าหมาย (In Target)', class: 'bg-success text-white' };
      if (inr <= 3.9) return { text: 'สูงกว่าเป้าหมายเล็กน้อย', class: 'bg-warning text-dark' };
      if (inr <= 4.9) return { text: 'สูงกว่าเป้าหมาย (เสี่ยงเลือดออก)', class: 'bg-warning-subtle text-warning-emphasis' };
      if (inr <= 8.9) return { text: 'สูงมาก (อันตราย)', class: 'bg-danger text-white' };
      return { text: 'วิกฤต (Critical High)', class: 'bg-danger text-white animate-pulse' };
    },

    baseEvaluation() {
      const inr = Number(this.inrValue) || 0;
      const b = this.computedCurrentWeeklyDose;
      const bleeding = this.hasMajorBleeding;
      const firstVisit = this.isFirstVisitOffTarget;

      // Special check: First visit off target within ±0.5 (1.5 - 3.5)
      if (firstVisit && inr >= 1.5 && inr <= 3.5 && (inr < 2.0 || inr > 3.0)) {
        return {
          nodeKey: 'RM',
          nodeLabel: 'RM (คงยาเดิม)',
          formulaLabel: '± 0.5 Target ครั้งแรก',
          title: 'คงขนาดยาเดิม (RM) & นัดตรวจซ้ำ',
          defaultPercent: 0,
          protocolRangeText: 'คงขนาดยาเดิม (0%)',
          percentOptions: [
            { label: 'คงเดิม (0%)', value: 0, isDefault: true },
            { label: '+5%', value: 5 },
            { label: '-5%', value: -5 }
          ],
          allowAdjustment: true,
          actionRecommendation: 'ตรวจพบออกนอกเป้าหมายครั้งแรกไม่เกิน ±0.5 ให้คงขนาดยาเดิม และนัดตรวจติดตาม 1 - 2 สัปดาห์',
          omitDoseText: null,
          vitKText: null,
          followUpText: 'นัดตรวจติดตาม INR ใน 1 - 2 สัปดาห์',
          badgeClass: 'bg-success text-white',
          bannerBgClass: 'bg-success-subtle border-success-subtle',
          textClass: 'text-success'
        };
      }

      // Branch 1: < 1.5 (Node C)
      if (inr > 0 && inr < 1.5) {
        return {
          nodeKey: 'C',
          nodeLabel: 'เกณฑ์ C (+10-20% * B)',
          formulaLabel: 'C = B + (10 - 20%)',
          title: 'ปรับเพิ่มขนาดยา 10% - 20%',
          defaultPercent: 15,
          protocolRangeText: 'ปรับเพิ่ม 10% ถึง 20%',
          percentOptions: [
            { label: '+10%', value: 10 },
            { label: '+12.5%', value: 12.5 },
            { label: '+15%', value: 15, isDefault: true },
            { label: '+17.5%', value: 17.5 },
            { label: '+20%', value: 20 }
          ],
          allowAdjustment: true,
          actionRecommendation: 'ปรับเพิ่มขนาดยา 10% - 20% ของขนาดยาเดิมต่อสัปดาห์ (แนะนำ +15%)',
          omitDoseText: null,
          vitKText: null,
          followUpText: 'นัดตรวจติดตาม INR ซ้ำใน 1 - 2 สัปดาห์',
          badgeClass: 'bg-info text-white',
          bannerBgClass: 'bg-info-subtle border-info-subtle',
          textClass: 'text-primary'
        };
      }

      // Branch 2: 1.5 - 1.9 (Node D)
      if (inr >= 1.5 && inr <= 1.9) {
        return {
          nodeKey: 'D',
          nodeLabel: 'เกณฑ์ D (+5-10% * B)',
          formulaLabel: 'D = B + (5 - 10%)',
          title: 'ปรับเพิ่มขนาดยา 5% - 10%',
          defaultPercent: 7.5,
          protocolRangeText: 'ปรับเพิ่ม 5% ถึง 10%',
          percentOptions: [
            { label: '+5%', value: 5 },
            { label: '+7.5%', value: 7.5, isDefault: true },
            { label: '+10%', value: 10 }
          ],
          allowAdjustment: true,
          actionRecommendation: 'ปรับเพิ่มขนาดยา 5% - 10% ของขนาดยาเดิมต่อสัปดาห์ (แนะนำ +7.5% ถึง +10%)',
          omitDoseText: null,
          vitKText: null,
          followUpText: 'นัดตรวจติดตาม INR ซ้ำใน 1 - 2 สัปดาห์',
          badgeClass: 'bg-info-subtle text-info-emphasis border border-info-subtle',
          bannerBgClass: 'bg-info-subtle border-info-subtle',
          textClass: 'text-info-emphasis'
        };
      }

      // Branch 3: 2.0 - 3.0 (Target -> RM)
      if (inr >= 2.0 && inr <= 3.0) {
        return {
          nodeKey: 'RM',
          nodeLabel: 'เกณฑ์ RM (คงยาเดิม)',
          formulaLabel: 'RM = B (คงเดิม)',
          title: 'อยู่ในเกณฑ์เป้าหมาย (คงขนาดยาเดิม)',
          defaultPercent: 0,
          protocolRangeText: 'คงขนาดยาเดิม (0%)',
          percentOptions: [
            { label: 'คงเดิม (0%)', value: 0, isDefault: true },
            { label: '+5%', value: 5 },
            { label: '-5%', value: -5 }
          ],
          allowAdjustment: true,
          actionRecommendation: 'ระดับยาอยู่ในช่วงการรักษาที่เหมาะสม ให้รับประทานขนาดยาเดิมต่อไป',
          omitDoseText: null,
          vitKText: null,
          followUpText: 'นัดตรวจติดตาม INR ทุก 4 - 8 สัปดาห์',
          badgeClass: 'bg-success text-white',
          bannerBgClass: 'bg-success-subtle border-success-subtle',
          textClass: 'text-success'
        };
      }

      // Branch 4: 3.1 - 3.9 (Node E)
      if (inr >= 3.1 && inr <= 3.9) {
        return {
          nodeKey: 'E',
          nodeLabel: 'เกณฑ์ E (ลด 5-10% * B)',
          formulaLabel: 'E = B - (5 - 10%)',
          title: 'ปรับลดขนาดยาลง 5% - 10%',
          defaultPercent: -7.5,
          protocolRangeText: 'ปรับลด 5% ถึง 10%',
          percentOptions: [
            { label: '-5%', value: -5 },
            { label: '-7.5%', value: -7.5, isDefault: true },
            { label: '-10%', value: -10 }
          ],
          allowAdjustment: true,
          actionRecommendation: 'ปรับลดขนาดยาลง 5% - 10% ของขนาดยาเดิมต่อสัปดาห์',
          omitDoseText: null,
          vitKText: null,
          followUpText: 'นัดตรวจติดตาม INR ซ้ำใน 1 - 2 สัปดาห์',
          badgeClass: 'bg-warning text-dark',
          bannerBgClass: 'bg-warning-subtle border-warning-subtle',
          textClass: 'text-warning-emphasis'
        };
      }

      // Branch 5: 4.0 - 4.9 (Node F)
      if (inr >= 4.0 && inr <= 4.9) {
        return {
          nodeKey: 'F',
          nodeLabel: 'เกณฑ์ F (ลด 10% + Omit 1 dose)',
          formulaLabel: 'F = B - 10% (+) Omit 1 dose',
          title: 'หยุดยา 1 มื้อ + ปรับลดลง 10%',
          defaultPercent: -10,
          protocolRangeText: 'ปรับลด 10%',
          percentOptions: [
            { label: '-5%', value: -5 },
            { label: '-10%', value: -10, isDefault: true },
            { label: '-15%', value: -15 }
          ],
          allowAdjustment: true,
          actionRecommendation: 'หยุดยา Warfarin 1 มื้อในวันแรก แล้วจึงเริ่มทานขนาดใหม่ที่ปรับลดลง 10%',
          omitDoseText: 'หยุดยา 1 มื้อในวันแรก',
          vitKText: null,
          followUpText: 'นัดตรวจติดตาม INR ใน 1 สัปดาห์',
          badgeClass: 'bg-warning-subtle text-danger border border-warning',
          bannerBgClass: 'bg-warning-subtle border-warning-subtle',
          textClass: 'text-danger'
        };
      }

      // Branch 6: 5.0 - 8.9 (Node G)
      if (inr >= 5.0 && inr <= 8.9) {
        if (!bleeding) {
          return {
            nodeKey: 'G',
            nodeLabel: 'เกณฑ์ G (ลด 20% + Omit + Vit K)',
            formulaLabel: 'G = B - 20% (+) Omit 1-2 dose + Vit K1',
            title: 'หยุดยา 1-2 มื้อ + Vit K1 oral + ลด 20%',
            defaultPercent: -20,
            protocolRangeText: 'ปรับลด 20%',
            percentOptions: [
              { label: '-15%', value: -15 },
              { label: '-20%', value: -20, isDefault: true },
              { label: '-25%', value: -25 }
            ],
            allowAdjustment: true,
            actionRecommendation: 'หยุดยา Warfarin 1 - 2 มื้อ + ให้ Vitamin K1 1 - 2.5 mg ชนิดรับประทาน (Oral) และปรับลดขนาดยาลง 20%',
            omitDoseText: 'หยุดยา 1 - 2 มื้อ',
            vitKText: 'ให้ Vitamin K1 1 - 2.5 mg รับประทาน (Oral)',
            followUpText: 'ตรวจติดตาม INR อย่างใกล้ชิดใน 24 - 48 ชั่วโมง',
            badgeClass: 'bg-danger text-white',
            bannerBgClass: 'bg-danger-subtle border-danger-subtle',
            textClass: 'text-danger'
          };
        } else {
          return {
            nodeKey: 'G_BLEED',
            nodeLabel: 'เกณฑ์ G (Major Bleeding)',
            formulaLabel: 'Emergency: Vit K 10 mg IV',
            title: '⚠️ ภาวะเลือดออกรุนแรง: หยุดยาทันที + Vit K 10 mg IV',
            defaultPercent: -100,
            protocolRangeText: 'หยุดยาทันที',
            percentOptions: [],
            allowAdjustment: false,
            actionRecommendation: 'หยุดยา Warfarin ทันที + ให้ Vitamin K1 10 mg ทางหลอดเลือดดำ (IV ช้าๆ) และปรึกษาแพทย์ให้ FFP/PCC ด่วน',
            omitDoseText: 'หยุดยา Warfarin ทันที',
            vitKText: 'Vitamin K1 10 mg IV ช้าๆ (Infusion)',
            followUpText: 'เฝ้าระวังและติดตาม INR ในโรงพยาบาลอย่างใกล้ชิด',
            badgeClass: 'bg-danger text-white',
            bannerBgClass: 'bg-danger-subtle border-danger',
            textClass: 'text-danger'
          };
        }
      }

      // Branch 7: >= 9.0
      if (inr >= 9.0) {
        if (!bleeding) {
          return {
            nodeKey: 'GT9',
            nodeLabel: 'เกณฑ์ ≥ 9.0 (No Bleeding)',
            formulaLabel: 'Vit K 5-10 mg oral, ลด > 20%',
            title: 'หยุดยา + Vit K 5-10 mg oral + ลด > 20%',
            defaultPercent: -25,
            protocolRangeText: 'ปรับลด > 20%',
            percentOptions: [
              { label: '-20%', value: -20 },
              { label: '-25%', value: -25, isDefault: true },
              { label: '-30%', value: -30 }
            ],
            allowAdjustment: true,
            actionRecommendation: 'หยุดยา Warfarin ทันที + ให้ Vitamin K1 5 - 10 mg รับประทาน (Oral) เมื่อ INR ลดลงใกล้ Target จึงเริ่มยาใหม่ขนาดลดลง > 20%',
            omitDoseText: 'หยุดยา Warfarin ชั่วคราว',
            vitKText: 'ให้ Vitamin K1 5 - 10 mg รับประทาน (Oral)',
            followUpText: 'ตรวจติดตาม INR ซ้ำใน 24 ชั่วโมง',
            badgeClass: 'bg-danger text-white',
            bannerBgClass: 'bg-danger-subtle border-danger-subtle',
            textClass: 'text-danger'
          };
        } else {
          return {
            nodeKey: 'GT9_BLEED',
            nodeLabel: 'เกณฑ์ ≥ 9.0 (Major Bleeding)',
            formulaLabel: 'Emergency: Vit K 10 mg IV',
            title: '⚠️ วิกฤตเลือดออกรุนแรง: Vit K 10 mg IV + FFP',
            defaultPercent: -100,
            protocolRangeText: 'หยุดยาทันที',
            percentOptions: [],
            allowAdjustment: false,
            actionRecommendation: 'หยุดยา Warfarin ทันที + ให้ Vitamin K1 10 mg IV ช้าๆ + เตรียมให้ FFP / Prothrombin Complex Concentrate',
            omitDoseText: 'หยุดยา Warfarin ทันที',
            vitKText: 'Vitamin K1 10 mg IV ช้าๆ',
            followUpText: 'ติดตามอาการและสัญญาณชีพในหอผู้ป่วยวิกฤต',
            badgeClass: 'bg-danger text-white',
            bannerBgClass: 'bg-danger-subtle border-danger',
            textClass: 'text-danger'
          };
        }
      }

      // Default fallback
      return {
        nodeKey: 'NONE',
        nodeLabel: '-',
        formulaLabel: '-',
        title: 'กรุณากรอกค่า INR',
        defaultPercent: 0,
        protocolRangeText: '-',
        percentOptions: [],
        allowAdjustment: false,
        actionRecommendation: 'กรุณาระบุค่าผลเลือด INR เพื่อคำนวณขนาดยา',
        omitDoseText: null,
        vitKText: null,
        followUpText: '-',
        badgeClass: 'bg-secondary text-white',
        bannerBgClass: 'bg-light border',
        textClass: 'text-dark'
      };
    },

    effectivePercent() {
      if (this.manualPercent !== null && this.manualPercent !== undefined && !isNaN(this.manualPercent)) {
        return Number(this.manualPercent);
      }
      return this.baseEvaluation ? this.baseEvaluation.defaultPercent : 0;
    },

    currentPercent() {
      return this.effectivePercent;
    },

    isCustomPercent() {
      return this.manualPercent !== null && this.baseEvaluation && this.manualPercent !== this.baseEvaluation.defaultPercent;
    },

    defaultPercentText() {
      const def = this.baseEvaluation ? this.baseEvaluation.defaultPercent : 0;
      if (def > 0) return `+${def}%`;
      if (def < 0) return `${def}%`;
      return `0%`;
    },

    evaluation() {
      const base = this.baseEvaluation;
      const b = this.computedCurrentWeeklyDose;
      const pct = this.effectivePercent;

      if (!base) {
        return {
          nodeKey: 'NONE',
          nodeLabel: '-',
          formulaLabel: '-',
          title: 'กรุณากรอกค่า INR',
          percentChange: 0,
          percentText: '-',
          newWeeklyDose: b,
          actionRecommendation: 'กรุณาระบุค่าผลเลือด INR เพื่อคำนวณขนาดยา',
          omitDoseText: null,
          vitKText: null,
          followUpText: '-',
          badgeClass: 'bg-secondary text-white',
          bannerBgClass: 'bg-light border',
          textClass: 'text-dark'
        };
      }

      if (base.nodeKey === 'G_BLEED' || base.nodeKey === 'GT9_BLEED' || !base.allowAdjustment) {
        return {
          ...base,
          percentChange: base.defaultPercent,
          percentText: 'หยุดยาทันที',
          newWeeklyDose: 0,
          rawNewDose: 0
        };
      }

      if (base.nodeKey === 'NONE' || !b || b <= 0) {
        return {
          ...base,
          percentChange: 0,
          percentText: '-',
          newWeeklyDose: 0,
          rawNewDose: 0
        };
      }

      const factor = 1 + (pct / 100);
      let rawNewDose = b * factor;
      if (rawNewDose < 0) rawNewDose = 0;

      // Exact mathematical calculation according to user formula: New Dose = B * (1 + C/100)
      const mathDose = Math.round(rawNewDose * 100) / 100;

      let formattedDose = '0';
      if (mathDose > 0) {
        if (mathDose % 1 === 0) {
          formattedDose = mathDose.toFixed(0);
        } else if (Math.abs((Math.round(mathDose * 10) / 10) - mathDose) < 0.0001) {
          formattedDose = mathDose.toFixed(1);
        } else {
          formattedDose = mathDose.toFixed(2);
        }
      }

      let pctText = 'คงเดิม (0%)';
      if (pct > 0) {
        pctText = `+${pct}%`;
      } else if (pct < 0) {
        pctText = `${pct}%`;
      }

      return {
        ...base,
        percentChange: pct,
        percentText: pctText,
        newWeeklyDose: mathDose,
        exactMathDoseFormatted: formattedDose,
        rawNewDose: rawNewDose,
        factorFormatted: (Math.round(factor * 1000) / 1000).toString()
      };
    },

    scheduleWeeklyTotal() {
      if (!this.dailySchedule || this.dailySchedule.length === 0) return 0;
      const sum = this.dailySchedule.reduce((acc, d) => acc + (Number(d.dose) || 0), 0);
      return Math.round(sum * 10) / 10;
    },

    dailySchedule() {
      const days = [
        { name: 'จันทร์', key: 'mon' },
        { name: 'อังคาร', key: 'tue' },
        { name: 'พุธ', key: 'wed' },
        { name: 'พฤหัสบดี', key: 'thu' },
        { name: 'ศุกร์', key: 'fri' },
        { name: 'เสาร์', key: 'sat' },
        { name: 'อาทิตย์', key: 'sun' }
      ];

      const targetWeekly = this.evaluation.newWeeklyDose;
      if (!targetWeekly || targetWeekly <= 0) {
        return days.map(d => ({
          name: d.name,
          key: d.key,
          dose: 0,
          isOmit: true,
          pills: [],
          pillsDescription: 'งดยา'
        }));
      }

      // Check if day 1 is omitted (for branch F or G)
      const omitFirstDay = this.evaluation.nodeKey === 'F' || this.evaluation.nodeKey === 'G';
      const doses = this.computeOptimalDailyDoses(targetWeekly, omitFirstDay);

      return days.map((d, i) => {
        const dayDose = doses[i] !== undefined ? doses[i] : 0;
        const isOmit = (i === 0 && omitFirstDay) || dayDose === 0;
        const pills = isOmit ? [] : this.getDayPillBreakdown(dayDose);

        let desc = '';
        if (isOmit) {
          desc = i === 0 && omitFirstDay ? 'งดยา 1 มื้อ' : 'งดยา';
        } else if (pills && pills.length > 0) {
          desc = pills.map(p => `${p.strength}mg (${p.qtyText})`).join(' + ');
        } else {
          desc = `${dayDose} mg`;
        }

        return {
          name: d.name,
          key: d.key,
          dose: dayDose,
          isOmit: isOmit,
          pills: pills,
          pillsDescription: desc
        };
      });
    },

    // Active dataset for printable WF SmartDose sheet (either current calculation or past history item)
    activePrintData() {
      if (this.printRecordData) {
        return {
          hn: this.printRecordData.hn || '',
          patientName: this.printRecordData.patient_name || '',
          prevWeeklyDose: Number(this.printRecordData.prev_weekly_dose) || 0,
          newWeeklyDose: Number(this.printRecordData.new_weekly_dose) || 0,
          percentChange: this.printRecordData.percent_change !== null ? Number(this.printRecordData.percent_change) : 0,
          schedule: this.printRecordData.daily_schedule || [],
          createdDate: this.printRecordData.created_at || null,
          createdBy: this.printRecordData.created_by || ''
        };
      }
      return {
        hn: this.patientHn || '',
        patientName: this.patientName || '',
        prevWeeklyDose: Number(this.computedCurrentWeeklyDose) || 0,
        newWeeklyDose: Number(this.evaluation.newWeeklyDose) || 0,
        percentChange: Number(this.evaluation.percentChange) || 0,
        schedule: this.dailySchedule || [],
        createdDate: new Date().toISOString(),
        createdBy: this.recordedBy || ''
      };
    },

    printPercentChangeText() {
      const p = this.activePrintData.percentChange;
      if (p === null || p === undefined) return '0%';
      const sign = p > 0 ? '+' : '';
      return `${sign}${Number(p).toFixed(1)}%`;
    },

    printDateFormatted() {
      const dtStr = this.activePrintData.createdDate;
      const d = dtStr ? new Date(typeof dtStr === 'string' ? dtStr.replace(' ', 'T') : dtStr) : new Date();
      if (isNaN(d.getTime())) return '';
      const months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
      return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear() + 543}`;
    },

    printScheduleRows() {
      const data = this.activePrintData;
      const schedule = data.schedule;
      if (!schedule || schedule.length === 0) return [];

      return schedule.map(day => {
        const dose = Number(day.dose) || 0;
        const isOmit = day.isOmit || dose === 0;
        const pills = isOmit ? [] : this.getDayPillBreakdown(dose);
        return {
          dayName: day.name,
          dose: dose,
          isOmit: isOmit,
          pills: pills
        };
      });
    },

    printInstructions() {
      const rows = this.printScheduleRows;
      if (!rows || rows.length === 0) return [];

      const map = new Map();
      const omitDays = [];

      rows.forEach(row => {
        if (row.isOmit || row.dose <= 0) {
          omitDays.push(row.dayName);
          return;
        }
        (row.pills || []).forEach(pill => {
          const key = `Warfarin ${pill.strength} mg (${pill.colorName}) รับประทาน ครั้งละ ${pill.qtyText} ก่อนนอน`;
          if (!map.has(key)) {
            map.set(key, []);
          }
          map.get(key).push(row.dayName);
        });
      });

      const lines = [];
      if (omitDays.length > 0) {
        lines.push(`งดยา วัน${omitDays.join(' ')}`);
      }

      for (const [key, days] of map.entries()) {
        if (days.length === 7) {
          lines.push(`${key} ทุกวัน (จันทร์ - อาทิตย์)`);
        } else {
          lines.push(`${key} วัน${days.join(' ')}`);
        }
      }

      return lines;
    },

    dispensePillBreakdown() {
      const schedule = this.dailySchedule;
      const duration = this.dispenseDays || 14;
      const weeks = duration / 7;
      const strength = this.primaryTabletStrength || 3;

      let primaryPillsCount = 0;
      let secondaryPillsCount = 0;
      let secondaryStrength = strength === 3 ? 2 : (strength === 2 ? 3 : 2);

      schedule.forEach(day => {
        if (!day.isOmit && day.dose > 0) {
          // If dose matches primary strength
          if (day.dose === strength) {
            primaryPillsCount += 1;
          } else if (day.dose === strength * 2) {
            primaryPillsCount += 2;
          } else if (day.dose === secondaryStrength) {
            secondaryPillsCount += 1;
          } else {
            // General calculation
            primaryPillsCount += day.dose / strength;
          }
        }
      });

      const totalPrimary = Math.ceil(primaryPillsCount * weeks);
      const totalSecondary = Math.ceil(secondaryPillsCount * weeks);

      const res = [
        { strength: strength, count: totalPrimary }
      ];

      if (totalSecondary > 0) {
        res.push({ strength: secondaryStrength, count: totalSecondary });
      }

      return res;
    },

    totalDispensedPills() {
      return this.dispensePillBreakdown.reduce((sum, p) => sum + p.count, 0);
    },

    prescriptionText() {
      const targetWeekly = this.evaluation.newWeeklyDose;
      if (!targetWeekly || targetWeekly <= 0) {
        return 'หยุดยา Warfarin ชั่วคราวตามคำสั่งแพทย์';
      }

      const schedule = this.dailySchedule;
      const hasVariation = schedule.some(d => d.dose !== schedule[1]?.dose);

      if (!hasVariation) {
        const d = schedule[1]?.dose || 3;
        return `Warfarin (${this.primaryTabletStrength} mg) รับประทานวันละ ${d} mg วันละ 1 ครั้ง ก่อนนอน ทุกวัน (รวมสัปดาห์ละ ${targetWeekly.toFixed(1)} mg) จ่ายจำนวน ${this.totalDispensedPills} เม็ด`;
      }

      const parts = schedule.map(d => `${d.name}: ${d.isOmit ? 'งด' : d.dose + 'mg'}`).join(', ');
      return `Warfarin รับประทานวันละ 1 ครั้ง ก่อนนอน ตามตาราง (${parts}) รวมสัปดาห์ละ ${targetWeekly.toFixed(1)} mg จ่าย ${this.dispenseDays} วัน รวม ${this.totalDispensedPills} เม็ด`;
    }
  },
  methods: {
    setDoseUnit(unit) {
      if (this.doseUnit !== unit) {
        if (unit === 'daily') {
          this.inputDoseValue = Math.round((this.inputDoseValue / 7) * 2) / 2 || 3;
        } else {
          this.inputDoseValue = Math.round(this.inputDoseValue * 7) || 21;
        }
        this.doseUnit = unit;
      }
    },

    setPercent(val) {
      this.manualPercent = Number(val);
    },

    onCustomPercentInput(val) {
      if (val === '' || val === null) {
        this.manualPercent = null;
        return;
      }
      const num = parseFloat(val);
      if (!isNaN(num)) {
        this.manualPercent = num;
      }
    },

    stepPercent(step) {
      const current = this.effectivePercent;
      const next = Math.round((current + step) * 10) / 10;
      this.manualPercent = next;
    },

    resetPercentToDefault() {
      this.manualPercent = null;
    },

    // จัดการเมื่อพิมพ์หรือเปลี่ยนค่าในตารางเครื่องมือคำนวณ
    onDoseCalcInput() {
      if (this.doseCalcAutoApply && this.doseCalcTotalDays > 0 && this.doseCalcGrandTotal > 0) {
        this.doseUnit = 'weekly';
        this.inputDoseValue = Number(this.doseCalcGrandTotal.toFixed(2));
      }
    },

    // นำค่าผลรวมจากเครื่องมือคำนวณไปใส่ในช่องขนาดยาเดิม (Current Dose)
    applyDoseCalcToCurrent() {
      if (this.doseCalcGrandTotal > 0) {
        this.doseUnit = 'weekly';
        this.inputDoseValue = Number(this.doseCalcGrandTotal.toFixed(2));
        if (typeof Swal !== 'undefined') {
          const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
          });
          Toast.fire({
            icon: 'success',
            title: `อัปเดตขนาดยาเดิม (B) เป็น ${this.inputDoseValue} มก./สัปดาห์ เรียบร้อย`
          });
        }
      }
    },

    // จัดการเมื่อเปลี่ยนตัวเลขในช่องจำนวนวัน
    // ล้างค่าที่กรอกในเครื่องมือคำนวณ
    clearDoseCalc() {
      this.doseCalc2mg_dose = 2;
      this.doseCalc2mg_days = null;
      this.doseCalc3mg_dose = 3;
      this.doseCalc3mg_days = null;
    },

    resetDefaults() {
      this.inrValue = 2.5;
      this.inputDoseValue = 21;
      this.doseUnit = 'weekly';
      this.hasMajorBleeding = false;
      this.isFirstVisitOffTarget = false;
      this.dispenseDays = 14;
      this.primaryTabletStrength = 3;
      this.manualPercent = null;
      this.clearDoseCalc();
    },

    getPillClass(strength) {
      if (strength === 2) return 'pill-orange';
      if (strength === 3) return 'pill-blue';
      if (strength === 5) return 'pill-pink';
      return 'pill-teal';
    },

    async copyPrescriptionText() {
      try {
        await navigator.clipboard.writeText(this.prescriptionText);
        Swal.fire({
          icon: 'success',
          title: 'คัดลอกข้อความแล้ว',
          text: this.prescriptionText,
          timer: 2000,
          showConfirmButton: false
        });
      } catch (err) {
        prompt('คัดลอกข้อความนี้เพื่อนำไปใช้งาน:', this.prescriptionText);
      }
    },

    // Patient lookup from HOSxP
    async lookupPatient() {
      if (!this.patientHn || !this.patientHn.trim()) return;
      this.loadingPatient = true;
      try {
        const res = await axios.get('/api-digital/warfarin/lookup_patient.php', {
          params: { hn: this.patientHn.trim() }
        });
        if (res.data && res.data.status === 'success' && res.data.data) {
          this.patientName = res.data.data.fullname || '';
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `พบผู้ป่วย: ${res.data.data.fullname} (อายุ ${res.data.data.age || '-'} ปี)`,
            showConfirmButton: false,
            timer: 3000
          });
        } else {
          Swal.fire({
            icon: 'info',
            title: 'ไม่พบข้อมูลใน HOSxP',
            text: res.data?.message || 'ไม่พบข้อมูลผู้ป่วยสำหรับ HN นี้ ท่านสามารถพิมพ์ชื่อผู้ป่วยเองได้',
            timer: 3000
          });
        }
      } catch (err) {
        console.error('Error looking up patient:', err);
      } finally {
        this.loadingPatient = false;
      }
    },

    // Save calculation record
    async saveCalculation() {
      if (!this.inrValue || this.inrValue <= 0) {
        Swal.fire({
          icon: 'warning',
          title: 'กรุณากรอกค่า INR',
          text: 'ต้องมีค่าผลเลือด INR (A) ก่อนทำการบันทึก'
        });
        return;
      }

      if (!this.computedCurrentWeeklyDose || this.computedCurrentWeeklyDose <= 0) {
        Swal.fire({
          icon: 'warning',
          title: 'กรุณากรอกขนาดยาเดิม',
          text: 'ต้องมีขนาดยาเดิมของผู้ป่วย (B) ก่อนทำการบันทึก'
        });
        return;
      }

      this.saving = true;
      if (this.recordedBy) {
        localStorage.setItem('warfarin_calc_user', this.recordedBy.trim());
      }

      const payload = {
        hn: this.patientHn ? this.patientHn.trim() : null,
        patient_name: this.patientName ? this.patientName.trim() : null,
        inr_value: Number(this.inrValue) || 0,
        prev_weekly_dose: Number(this.computedCurrentWeeklyDose) || 0,
        percent_change: Number(this.evaluation.percentChange) || 0,
        new_weekly_dose: Number(this.evaluation.newWeeklyDose) || 0,
        daily_avg_dose: Number((this.evaluation.newWeeklyDose / 7).toFixed(2)) || 0,
        formula_label: this.evaluation.formulaLabel || '',
        has_major_bleeding: this.hasMajorBleeding ? 1 : 0,
        is_first_visit: this.isFirstVisitOffTarget ? 1 : 0,
        primary_tablet_strength: Number(this.primaryTabletStrength) || 3,
        daily_schedule_json: this.dailySchedule,
        note: this.calculationNote ? this.calculationNote.trim() : null,
        created_by: this.recordedBy ? this.recordedBy.trim() : 'Pharmacist'
      };

      try {
        const res = await axios.post('/api-digital/warfarin/save_calculation.php', payload);
        if (res.data && res.data.status === 'success') {
          this.lastSavedId = res.data.id;
          const now = new Date();
          this.lastSavedTime = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });

          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ!',
            html: `
              <div class="mb-2">บันทึกประวัติการคำนวณเรียบร้อยแล้ว <small class="text-muted">(รหัส: #${res.data.id})</small></div>
              <div class="p-2 rounded bg-light border text-start small mb-3">
                <div><strong>ผู้ป่วย:</strong> ${this.patientName ? this.patientName : (this.patientHn ? 'HN ' + this.patientHn : 'ทั่วไป')}</div>
                <div><strong>ขนาดยาใหม่:</strong> ${this.evaluation.newWeeklyDose} mg/wk (${this.evaluation.percentChange >= 0 ? '+' : ''}${this.evaluation.percentChange}%)</div>
              </div>
              <div class="fw-bold text-primary">ต้องการพิมพ์ตารางการรับประทานยา (WF SmartDose) เลยหรือไม่?</div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-printer-fill me-1"></i> พิมพ์ตารางยา (WF SmartDose)',
            cancelButtonText: 'ไว้คราวหลัง',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            focusConfirm: true
          }).then((result) => {
            if (result.isConfirmed) {
              this.openPrintModal();
            }
          });

          this.fetchHistory();
        } else {
          throw new Error(res.data?.message || 'ไม่สามารถบันทึกข้อมูลได้');
        }
      } catch (err) {
        console.error('Error saving calculation:', err);
        Swal.fire({
          icon: 'error',
          title: 'บันทึกไม่สำเร็จ',
          text: err.response?.data?.message || err.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์'
        });
      } finally {
        this.saving = false;
      }
    },

    // Fetch calculation history list
    async fetchHistory() {
      this.loadingHistory = true;
      try {
        const res = await axios.get('/api-digital/warfarin/get_history.php', {
          params: {
            search: this.historySearch || '',
            start_date: this.historyStartDate || '',
            end_date: this.historyEndDate || '',
            limit: 100
          }
        });
        if (res.data && res.data.status === 'success') {
          this.historyList = res.data.data || [];
          this.historyTotalCount = res.data.count || this.historyList.length;
        }
      } catch (err) {
        console.error('Error fetching history:', err);
      } finally {
        this.loadingHistory = false;
      }
    },

    openHistoryModal() {
      this.showHistoryModal = true;
      this.fetchHistory();
    },

    closeHistoryModal() {
      this.showHistoryModal = false;
    },

    clearHistoryFilter() {
      this.historySearch = '';
      this.historyStartDate = '';
      this.historyEndDate = '';
      this.fetchHistory();
    },

    // Load past record back into calculator
    loadRecordIntoCalculator(item) {
      if (!item) return;
      this.patientHn = item.hn || '';
      this.patientName = item.patient_name || '';
      this.inrValue = Number(item.inr_value) || 2.5;
      this.inputDoseValue = Number(item.prev_weekly_dose) || 21;
      this.doseUnit = 'weekly';
      this.hasMajorBleeding = item.has_major_bleeding == 1;
      this.isFirstVisitOffTarget = item.is_first_visit == 1;
      this.primaryTabletStrength = Number(item.primary_tablet_strength) || 3;
      this.manualPercent = item.percent_change !== null ? Number(item.percent_change) : null;
      this.calculationNote = item.note || '';
      if (item.created_by) {
        this.recordedBy = item.created_by;
      }
      this.clearDoseCalc();
      this.showHistoryModal = false;

      Swal.fire({
        icon: 'info',
        title: 'ดึงข้อมูลสำเร็จ',
        html: `ดึงข้อมูลของ <b>${item.patient_name || item.hn || 'รายการ #' + item.id}</b> กลับมาที่หน้าคำนวณแล้ว`,
        timer: 2000,
        showConfirmButton: false
      });

      window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    // Delete record with confirmation
    async confirmDeleteRecord(item) {
      if (!item) return;
      const result = await Swal.fire({
        title: 'ยืนยันการลบรายการนี้?',
        html: `ต้องการลบประวัติของ <b>${item.patient_name || item.hn || '#' + item.id}</b> ใช่หรือไม่?<br><small class="text-danger">เมื่อลบแล้วจะไม่สามารถกู้คืนได้</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash me-1"></i>ยืนยันลบ',
        cancelButtonText: 'ยกเลิก'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/warfarin/delete_calculation.php', { id: item.id });
          if (res.data && res.data.status === 'success') {
            Swal.fire({
              toast: true,
              position: 'top-end',
              icon: 'success',
              title: 'ลบรายการเรียบร้อยแล้ว',
              showConfirmButton: false,
              timer: 2500
            });
            this.fetchHistory();
          } else {
            throw new Error(res.data?.message || 'ไม่สามารถลบได้');
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: err.response?.data?.message || err.message || 'ไม่สามารถลบรายการได้'
          });
        }
      }
    },

    // Export history to CSV (Excel compatible)
    exportHistoryExcel() {
      if (!this.historyList || this.historyList.length === 0) {
        Swal.fire({ icon: 'warning', title: 'ไม่มีข้อมูล', text: 'ไม่มีรายการประวัติสำหรับส่งออก' });
        return;
      }

      const headers = [
        'ลำดับ',
        'วันที่บันทึก',
        'HN',
        'ชื่อผู้ป่วย',
        'ค่า INR (A)',
        'ผลการประเมิน',
        'ขนาดยาเดิม (มก./สัปดาห์)',
        'เปอร์เซ็นต์ปรับยา (C %)',
        'ขนาดยาใหม่ (มก./สัปดาห์)',
        'เฉลี่ยต่อวัน (มก./วัน)',
        'ขนาดเม็ดยาหลัก (มก.)',
        'มีภาวะเลือดออกรุนแรง',
        'พบหลุด Target ครั้งแรก',
        'ผู้บันทึก',
        'หมายเหตุ'
      ];

      const rows = this.historyList.map((item, index) => [
        index + 1,
        `"${item.created_at || ''}"`,
        `"${item.hn || ''}"`,
        `"${(item.patient_name || '').replace(/"/g, '""')}"`,
        item.inr_value,
        `"${(item.formula_label || '').replace(/"/g, '""')}"`,
        item.prev_weekly_dose,
        item.percent_change,
        item.new_weekly_dose,
        item.daily_avg_dose,
        item.primary_tablet_strength,
        item.has_major_bleeding == 1 ? 'ใช่' : 'ไม่ใช่',
        item.is_first_visit == 1 ? 'ใช่' : 'ไม่ใช่',
        `"${(item.created_by || '').replace(/"/g, '""')}"`,
        `"${(item.note || '').replace(/"/g, '""')}"`
      ]);

      const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(r => r.join(','))].join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
      link.setAttribute('href', url);
      link.setAttribute('download', `warfarin_calc_history_${dateStr}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    },

    formatDateTime(dtStr) {
      if (!dtStr) return '-';
      try {
        const d = new Date(dtStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return dtStr;
        const months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const day = d.getDate();
        const month = months[d.getMonth()];
        const year = d.getFullYear() + 543;
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        return `${day} ${month} ${year} ${hours}:${minutes} น.`;
      } catch (e) {
        return dtStr;
      }
    },

    getInrBadgeClass(val) {
      const inr = Number(val);
      if (!inr || inr <= 0) return 'bg-secondary text-white';
      if (inr < 2.0) return 'bg-info text-white';
      if (inr <= 3.0) return 'bg-success text-white';
      if (inr <= 4.9) return 'bg-warning text-dark';
      return 'bg-danger text-white';
    },

    // Clinically optimal weekly dose distribution (Weekdays 5d / Weekends 2d like WF SmartDose nomogram)
    computeOptimalDailyDoses(targetWeekly, omitFirstDay = false) {
      if (!targetWeekly || targetWeekly <= 0) {
        return [0, 0, 0, 0, 0, 0, 0];
      }

      // If omitFirstDay (Monday = 0):
      if (omitFirstDay) {
        const avg = targetWeekly / 6;
        const roundedAvg = Math.round(avg * 2) / 2;
        if (Math.abs(roundedAvg * 6 - targetWeekly) < 0.05) {
          return [0, roundedAvg, roundedAvg, roundedAvg, roundedAvg, roundedAvg, roundedAvg];
        }
        for (let delta = -1.0; delta <= 1.0; delta += 0.5) {
          const A = Math.round((avg + delta) * 2) / 2;
          const B = (targetWeekly - 4 * A) / 2;
          const roundedB = Math.round(B * 2) / 2;
          if (A > 0 && B > 0 && Math.abs(B - roundedB) < 0.001 && Math.abs(A - roundedB) <= 1.0) {
            return [0, A, A, A, A, roundedB, roundedB];
          }
        }
      } else {
        const avg = targetWeekly / 7;
        // 1. Equal all 7 days
        const roundedAvg = Math.round(avg * 2) / 2;
        if (Math.abs(roundedAvg * 7 - targetWeekly) < 0.05) {
          return [roundedAvg, roundedAvg, roundedAvg, roundedAvg, roundedAvg, roundedAvg, roundedAvg];
        }

        // 2. Weekday (5 days: Mon-Fri) at A, Weekend (2 days: Sat-Sun) at B
        let bestCandidate = null;
        let minDiff = 999;
        for (let delta = -1.0; delta <= 1.0; delta += 0.5) {
          const A = Math.round((avg + delta) * 2) / 2;
          const B = (targetWeekly - 5 * A) / 2;
          const roundedB = Math.round(B * 2) / 2;
          if (A > 0 && B > 0 && Math.abs(B - roundedB) < 0.001) {
            const diff = Math.abs(A - roundedB);
            if (diff <= 1.0 && diff < minDiff) {
              minDiff = diff;
              bestCandidate = [A, A, A, A, A, roundedB, roundedB];
            }
          }
        }
        if (bestCandidate) {
          return bestCandidate;
        }

        // 3. Mon-Thu (4 days) at A, Fri-Sun (3 days) at B
        for (let delta = -1.0; delta <= 1.0; delta += 0.5) {
          const A = Math.round((avg + delta) * 2) / 2;
          const B = (targetWeekly - 4 * A) / 3;
          const roundedB = Math.round(B * 2) / 2;
          if (A > 0 && B > 0 && Math.abs(B - roundedB) < 0.001) {
            const diff = Math.abs(A - roundedB);
            if (diff <= 1.0 && diff < minDiff) {
              minDiff = diff;
              bestCandidate = [A, A, A, A, roundedB, roundedB, roundedB];
            }
          }
        }
        if (bestCandidate) {
          return bestCandidate;
        }

        // 4. 6 days at A, 1 day at B (Sunday)
        for (let delta = -1.0; delta <= 1.0; delta += 0.5) {
          const A = Math.round((avg + delta) * 2) / 2;
          const B = targetWeekly - 6 * A;
          const roundedB = Math.round(B * 2) / 2;
          if (A > 0 && B > 0 && Math.abs(B - roundedB) < 0.001 && Math.abs(A - roundedB) <= 1.0) {
            return [A, A, A, A, A, A, roundedB];
          }
        }
      }

      // 5. Fallback greedy
      const res = [];
      let accumulated = 0;
      for (let i = 0; i < 7; i++) {
        if (i === 0 && omitFirstDay) {
          res.push(0);
          continue;
        }
        const remainingDays = 7 - res.length;
        const remainingDose = targetWeekly - accumulated;
        let dayDose = Math.round((remainingDose / remainingDays) * 2) / 2;
        if (dayDose < 0) dayDose = 0;
        accumulated += dayDose;
        res.push(dayDose);
      }
      return res;
    },

    // Daily dose breakdown into standard hospital Warfarin pills (2mg orange & 3mg blue)
    getDayPillBreakdown(dose) {
      if (!dose || dose <= 0) return [];
      const rounded = Math.round(dose * 10) / 10;

      switch (rounded) {
        case 1.0:
          return [
            { strength: 2, colorName: 'สีส้ม', qtyText: 'ครึ่งเม็ด', qtyNum: 0.5, circleClass: 'wf-circle-orange-half' }
          ];
        case 1.5:
          return [
            { strength: 3, colorName: 'สีฟ้า', qtyText: 'ครึ่งเม็ด', qtyNum: 0.5, circleClass: 'wf-circle-blue-half' }
          ];
        case 2.0:
          return [
            { strength: 2, colorName: 'สีส้ม', qtyText: '1 เม็ด', qtyNum: 1.0, circleClass: 'wf-circle-orange-full' }
          ];
        case 2.5:
          return [
            { strength: 2, colorName: 'สีส้ม', qtyText: 'ครึ่งเม็ด', qtyNum: 0.5, circleClass: 'wf-circle-orange-half' },
            { strength: 3, colorName: 'สีฟ้า', qtyText: 'ครึ่งเม็ด', qtyNum: 0.5, circleClass: 'wf-circle-blue-half' }
          ];
        case 3.0:
          return [
            { strength: 3, colorName: 'สีฟ้า', qtyText: '1 เม็ด', qtyNum: 1.0, circleClass: 'wf-circle-blue-full' }
          ];
        case 3.5:
          return [
            { strength: 2, colorName: 'สีส้ม', qtyText: '1 เม็ด', qtyNum: 1.0, circleClass: 'wf-circle-orange-full' },
            { strength: 3, colorName: 'สีฟ้า', qtyText: 'ครึ่งเม็ด', qtyNum: 0.5, circleClass: 'wf-circle-blue-half' }
          ];
        case 4.0:
          return [
            { strength: 2, colorName: 'สีส้ม', qtyText: '2 เม็ด', qtyNum: 2.0, circleClass: 'wf-circle-orange-full' }
          ];
        case 4.5:
          return [
            { strength: 3, colorName: 'สีฟ้า', qtyText: '1 เม็ดครึ่ง', qtyNum: 1.5, circleClass: 'wf-circle-blue-full' }
          ];
        case 5.0:
          return [
            { strength: 2, colorName: 'สีส้ม', qtyText: '1 เม็ด', qtyNum: 1.0, circleClass: 'wf-circle-orange-full' },
            { strength: 3, colorName: 'สีฟ้า', qtyText: '1 เม็ด', qtyNum: 1.0, circleClass: 'wf-circle-blue-full' }
          ];
        case 5.5:
          return [
            { strength: 2, colorName: 'สีส้ม', qtyText: '2 เม็ด', qtyNum: 2.0, circleClass: 'wf-circle-orange-full' },
            { strength: 3, colorName: 'สีฟ้า', qtyText: 'ครึ่งเม็ด', qtyNum: 0.5, circleClass: 'wf-circle-blue-half' }
          ];
        case 6.0:
          return [
            { strength: 3, colorName: 'สีฟ้า', qtyText: '2 เม็ด', qtyNum: 2.0, circleClass: 'wf-circle-blue-full' }
          ];
        case 6.5:
          return [
            { strength: 2, colorName: 'สีส้ม', qtyText: '1 เม็ด', qtyNum: 1.0, circleClass: 'wf-circle-orange-full' },
            { strength: 3, colorName: 'สีฟ้า', qtyText: '1 เม็ดครึ่ง', qtyNum: 1.5, circleClass: 'wf-circle-blue-full' }
          ];
        case 7.0:
          return [
            { strength: 2, colorName: 'สีส้ม', qtyText: '2 เม็ด', qtyNum: 2.0, circleClass: 'wf-circle-orange-full' },
            { strength: 3, colorName: 'สีฟ้า', qtyText: '1 เม็ด', qtyNum: 1.0, circleClass: 'wf-circle-blue-full' }
          ];
        case 7.5:
          return [
            { strength: 2, colorName: 'สีส้ม', qtyText: '3 เม็ด', qtyNum: 3.0, circleClass: 'wf-circle-orange-full' },
            { strength: 3, colorName: 'สีฟ้า', qtyText: 'ครึ่งเม็ด', qtyNum: 0.5, circleClass: 'wf-circle-blue-half' }
          ];
        case 8.0:
          return [
            { strength: 2, colorName: 'สีส้ม', qtyText: '1 เม็ด', qtyNum: 1.0, circleClass: 'wf-circle-orange-full' },
            { strength: 3, colorName: 'สีฟ้า', qtyText: '2 เม็ด', qtyNum: 2.0, circleClass: 'wf-circle-blue-full' }
          ];
        default: {
          const items = [];
          let rem = rounded;
          const count3 = Math.floor(rem / 3);
          if (count3 > 0) {
            items.push({
              strength: 3,
              colorName: 'สีฟ้า',
              qtyText: `${count3} เม็ด`,
              qtyNum: count3,
              circleClass: 'wf-circle-blue-full'
            });
            rem -= count3 * 3;
          }
          rem = Math.round(rem * 10) / 10;
          if (rem === 2.0) {
            items.push({
              strength: 2,
              colorName: 'สีส้ม',
              qtyText: '1 เม็ด',
              qtyNum: 1.0,
              circleClass: 'wf-circle-orange-full'
            });
          } else if (rem === 1.5) {
            items.push({
              strength: 3,
              colorName: 'สีฟ้า',
              qtyText: 'ครึ่งเม็ด',
              qtyNum: 0.5,
              circleClass: 'wf-circle-blue-half'
            });
          } else if (rem === 1.0) {
            items.push({
              strength: 2,
              colorName: 'สีส้ม',
              qtyText: 'ครึ่งเม็ด',
              qtyNum: 0.5,
              circleClass: 'wf-circle-orange-half'
            });
          } else if (rem > 0) {
            items.push({
              strength: 3,
              colorName: 'สีฟ้า',
              qtyText: `${rem} mg`,
              qtyNum: rem / 3,
              circleClass: 'wf-circle-blue-full'
            });
          }
          return items;
        }
      }
    },

    openPrintModal(customData = null) {
      if (customData) {
        this.printRecordData = customData;
      } else {
        this.printRecordData = null;
      }
      this.showPrintModal = true;
    },

    closePrintModal() {
      this.showPrintModal = false;
      this.printRecordData = null;
    },

    triggerBrowserPrint() {
      window.print();
    },

    printPastRecord(item) {
      this.openPrintModal(item);
    }
  }
};
</script>

<style scoped>
.warfarin-calculator {
  font-family: 'Sarabun', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.calc-header {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 45%, #991b1b 100%);
}

.header-icon-circle {
  width: 52px;
  height: 52px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bg-shape {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  pointer-events: none;
}
.shape-1 {
  width: 220px;
  height: 220px;
  top: -80px;
  right: -40px;
}
.shape-2 {
  width: 140px;
  height: 140px;
  bottom: -60px;
  right: 180px;
}

.btn-xs {
  font-size: 11px;
  padding: 2px 8px;
}

.shadow-2xs {
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.dose-helper-card {
  background: linear-gradient(180deg, #fbfcfe 0%, #f1f5f9 100%);
  border-color: #cbd5e1 !important;
}

.dose-curly-col {
  min-width: 22px;
}

.total-summary-card {
  min-width: 105px;
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border: 1.5px dashed #f59e0b !important;
}

.pill-visual-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.pill-orange {
  background: linear-gradient(135deg, #fed7aa, #fb923c);
  color: #7c2d12;
  border: 1.5px solid #f97316;
}

.pill-blue {
  background: linear-gradient(135deg, #bae6fd, #38bdf8);
  color: #0369a1;
  border: 1.5px solid #0284c7;
}

.pill-pink {
  background: linear-gradient(135deg, #fbcfe8, #f472b6);
  color: #831843;
  border: 1.5px solid #db2777;
}

.pill-teal {
  background: linear-gradient(135deg, #99f6e4, #2dd4bf);
  color: #115e59;
  border: 1.5px solid #0d9488;
}

/* Flowchart styling */
.flowchart-container {
  position: relative;
}

.flow-node {
  border-width: 2px !important;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.flow-node.node-active {
  transform: scale(1.05);
  box-shadow: 0 10px 25px -5px rgba(220, 38, 38, 0.25), 0 0 0 3px #dc2626 !important;
  border-color: #dc2626 !important;
  background-color: #fef2f2 !important;
  z-index: 10;
}

.target-node.node-active {
  box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.25), 0 0 0 3px #10b981 !important;
  border-color: #10b981 !important;
  background-color: #ecfdf5 !important;
}

.circle-badge {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 13px;
  flex-shrink: 0;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}

.circle-a {
  background: #3b82f6;
  color: #ffffff;
}

.circle-b {
  background: #8b5cf6;
  color: #ffffff;
}

.circle-c {
  background: #0284c7;
  color: #ffffff;
}

.circle-d {
  background: #0ea5e9;
  color: #ffffff;
}

.circle-e {
  background: #f59e0b;
  color: #ffffff;
}

.circle-f {
  background: #ea580c;
  color: #ffffff;
}

.circle-g {
  background: #dc2626;
  color: #ffffff;
}

.center-hub-card {
  width: 170px;
  height: 170px;
  border: 3px solid #6366f1 !important;
}

@media (min-width: 768px) {
  .border-start-md {
    border-left: 1px solid #e2e8f0;
  }
}

/* History Modal Overlay & Container */
.history-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  overflow-y: auto;
}

.history-modal-container {
  background: #ffffff;
  border-radius: 1.25rem;
  max-width: 1200px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  animation: modalScaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.history-modal-body {
  max-height: 60vh;
}

@keyframes modalScaleIn {
  from {
    opacity: 0;
    transform: scale(0.96) translateY(10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* ==========================================================================
   WF SmartDose Print Modal & Sheet Styles (Matching Hospital Layout)
   ========================================================================== */
.wf-print-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  z-index: 1060;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  overflow-y: auto;
}

.wf-print-modal-dialog {
  background: #f8fafc;
  border-radius: 1.25rem;
  max-width: 820px;
  width: 100%;
  max-height: 94vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  animation: modalScaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.wf-print-modal-body {
  max-height: 80vh;
  background-color: #f1f5f9;
}

/* Printable Paper Sheet */
.wf-smartdose-sheet {
  max-width: 680px;
  width: 100%;
  padding: 32px 36px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  color: #1e293b;
  font-family: 'Sarabun', 'Prompt', -apple-system, BlinkMacSystemFont, sans-serif;
  line-height: 1.5;
}

.wf-sheet-title {
  font-size: 22px;
  letter-spacing: -0.02em;
  color: #1e293b;
}

.wf-sheet-hospital {
  font-size: 15px;
  color: #475569;
}

.wf-sheet-dose-line {
  font-size: 13.5px;
  color: #334155;
}

.wf-dose-meta strong {
  color: #0f172a;
}

/* 7-Day Table */
.wf-schedule-table {
  background-color: #ffffff;
  border-color: #e2e8f0 !important;
  overflow: hidden;
}

.wf-schedule-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  border-bottom: 1px solid #e2e8f0;
  transition: background-color 0.15s ease;
}

.wf-schedule-row:last-child {
  border-bottom: none;
}

.wf-col-day {
  width: 110px;
  flex-shrink: 0;
}

.wf-day-badge {
  display: inline-block;
  min-width: 88px;
  padding: 5px 12px;
  background-color: #64748b;
  color: #ffffff;
  font-weight: 700;
  font-size: 13.5px;
  text-align: center;
  border-radius: 6px;
  -webkit-print-color-adjust: exact !important;
  print-color-adjust: exact !important;
}

.wf-col-pills {
  flex: 1;
  padding: 0 16px;
}

.wf-pill-item {
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  margin: 2px 0;
}

.wf-pill-circle {
  display: inline-block;
  width: 15px;
  height: 15px;
  border-radius: 50%;
  margin-left: 8px;
  flex-shrink: 0;
  vertical-align: middle;
  -webkit-print-color-adjust: exact !important;
  print-color-adjust: exact !important;
}

/* Orange pills: 2 mg */
.wf-circle-orange-full {
  background-color: #f97316;
  border: 1.5px solid #c2410c;
}

.wf-circle-orange-half {
  background: linear-gradient(90deg, #f97316 50%, #ffffff 50%);
  border: 1.5px solid #c2410c;
}

/* Blue pills: 3 mg */
.wf-circle-blue-full {
  background-color: #0284c7;
  border: 1.5px solid #0369a1;
}

.wf-circle-blue-half {
  background: linear-gradient(90deg, #0284c7 50%, #ffffff 50%);
  border: 1.5px solid #0369a1;
}

.wf-col-timing {
  width: 100px;
  flex-shrink: 0;
}

.wf-timing-badge {
  display: inline-block;
  padding: 4px 16px;
  background-color: #f1f5f9;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
  border-radius: 50px;
  border: 1px solid #e2e8f0;
  -webkit-print-color-adjust: exact !important;
  print-color-adjust: exact !important;
}

/* Instruction box */
.wf-instruction-box {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  font-size: 13.5px;
  line-height: 1.85;
  color: #334155;
}

.wf-inst-line {
  margin-bottom: 2px;
}

.wf-inst-line:last-child {
  margin-bottom: 0;
}

.wf-footer-warning {
  font-size: 12px;
  color: #64748b;
}

/* ==========================================================================
   Print Rules (@media print)
   ========================================================================== */
@media print {
  /* Hide all calculator and application UI */
  body, html {
    background: #ffffff !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    height: auto !important;
  }

  .calc-header,
  .card,
  .row,
  .history-modal-overlay,
  .no-print,
  nav,
  .navbar,
  footer,
  .footer,
  .AppFooter {
    display: none !important;
  }

  .wf-print-modal-overlay {
    position: static !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    display: block !important;
    width: 100% !important;
    height: auto !important;
    overflow: visible !important;
    backdrop-filter: none !important;
    z-index: auto !important;
  }

  .wf-print-modal-dialog {
    box-shadow: none !important;
    border: none !important;
    background: transparent !important;
    max-width: 100% !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    border-radius: 0 !important;
    max-height: none !important;
    overflow: visible !important;
  }

  .wf-print-modal-body {
    background: transparent !important;
    padding: 0 !important;
    overflow: visible !important;
    max-height: none !important;
  }

  .wf-smartdose-sheet {
    display: block !important;
    box-shadow: none !important;
    border: 1px solid #94a3b8 !important;
    border-radius: 8px !important;
    padding: 24px 28px !important;
    margin: 0 auto !important;
    width: 100% !important;
    max-width: 680px !important;
    page-break-inside: avoid !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .wf-day-badge,
  .wf-pill-circle,
  .wf-timing-badge,
  .wf-instruction-box {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}
</style>
