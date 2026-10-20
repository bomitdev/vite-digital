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
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label fw-bold text-dark mb-0">
                  <span class="badge bg-primary text-white rounded-circle me-1 px-2 py-1">B</span>
                  ขนาดยาเดิมของผู้ป่วย (Current Dose)
                </label>
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
                      {{ evaluation.newWeeklyDose.toFixed(1) }}
                      <small class="fs-6 fw-normal text-muted">mg / สัปดาห์</small>
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
                  เป้าหมาย: {{ evaluation.newWeeklyDose.toFixed(1) }} mg/สัปดาห์
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
                <button
                  type="button"
                  class="btn btn-sm btn-outline-primary rounded-pill px-3"
                  @click="copyPrescriptionText"
                >
                  <i class="bi bi-clipboard me-1"></i>คัดลอกวิธีใช้ยา
                </button>
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
  </div>
</template>

<script>
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
      ]
    };
  },
  watch: {
    'baseEvaluation.nodeKey'(newKey, oldKey) {
      if (newKey !== oldKey) {
        this.manualPercent = null;
      }
    }
  },
  computed: {
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
      const roundedDose = Math.round(rawNewDose * 2) / 2;

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
        newWeeklyDose: roundedDose,
        rawNewDose: rawNewDose
      };
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
          dose: 0,
          isOmit: true,
          pillsDescription: 'งดยา'
        }));
      }

      // Check if day 1 is omitted (for branch F or G)
      const omitFirstDay = this.evaluation.nodeKey === 'F' || this.evaluation.nodeKey === 'G';
      const availableDaysCount = omitFirstDay ? 6 : 7;
      const avgDaily = targetWeekly / availableDaysCount;

      // Primary strength, e.g. 3 mg or 2 mg
      const strength = this.primaryTabletStrength || 3;

      // Distribute integer or half-tablet doses across days to match targetWeekly
      // Standard distribution: try steps of 0.5 or 1 pill
      const result = [];
      let accumulated = 0;

      for (let i = 0; i < 7; i++) {
        if (i === 0 && omitFirstDay) {
          result.push({
            name: days[i].name,
            dose: 0,
            isOmit: true,
            pillsDescription: 'งดยา 1 มื้อ'
          });
          continue;
        }

        // Calculate proportional day dose
        const remainingDays = 7 - result.length;
        const remainingDose = targetWeekly - accumulated;
        let dayDose = Math.round((remainingDose / remainingDays) * 2) / 2;
        if (dayDose < 0) dayDose = 0;

        accumulated += dayDose;

        // Pill breakdown description
        let desc = '';
        if (dayDose === strength) {
          desc = `1 เม็ด (${strength}mg)`;
        } else if (dayDose === strength * 2) {
          desc = `2 เม็ด (${strength}mg)`;
        } else if (dayDose === strength / 2) {
          desc = `1/2 เม็ด (${strength}mg)`;
        } else if (dayDose === 0) {
          desc = 'งดยา';
        } else {
          desc = `${dayDose} mg`;
        }

        result.push({
          name: days[i].name,
          dose: dayDose,
          isOmit: false,
          pillsDescription: desc
        });
      }

      return result;
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

    resetDefaults() {
      this.inrValue = 2.5;
      this.inputDoseValue = 21;
      this.doseUnit = 'weekly';
      this.hasMajorBleeding = false;
      this.isFirstVisitOffTarget = false;
      this.dispenseDays = 14;
      this.primaryTabletStrength = 3;
      this.manualPercent = null;
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
        alert('คัดลอกข้อความวิธีใช้ยาเรียบร้อยแล้ว: \n\n' + this.prescriptionText);
      } catch (err) {
        prompt('คัดลอกข้อความนี้เพื่อนำไปใช้งาน:', this.prescriptionText);
      }
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
</style>
