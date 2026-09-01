<template>
  <div class="report-container pt-3 pb-5 bg-light min-vh-100">
    <div class="container d-print-none mb-4">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0 text-purple fw-bold">
              <i class="bi bi-file-earmark-spreadsheet me-2"></i> ระบบรายงานเบิกจ่าย OT (ห้องบัตร)
            </h2>
            <button
              @click="$router.push('/manager-schedule')"
              class="btn btn-outline-secondary btn-sm"
            >
              <i class="bi bi-arrow-left me-1"></i> กลับตารางเวร
            </button>
          </div>

          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label fw-bold small text-muted">เลือกเดือน:</label>
              <select class="form-select" v-model="selectedMonth" @change="fetchData">
                <option v-for="(mName, index) in monthNames" :key="index" :value="index + 1">
                  {{ mName }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold small text-muted">เลือกปี (ค.ศ.):</label>
              <select class="form-select" v-model="selectedYear" @change="fetchData">
                <option
                  v-for="y in [currentYear - 1, currentYear, currentYear + 1]"
                  :key="y"
                  :value="y"
                >
                  {{ y }} ({{ y + 543 }})
                </option>
              </select>
            </div>
          </div>

          <div class="row mt-3 border-top pt-3 d-print-none">
            <div class="col-12 d-flex flex-wrap gap-2 justify-content-center align-items-center">
              <span class="fw-bold me-1 text-primary">ตารางเวรขอขึ้น:</span>
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary" @click="printReport('form1')">
                  <i class="bi bi-printer me-1"></i> พิมพ์
                </button>
                <button v-if="selectedMonth === 1 || selectedMonth === 4" class="btn btn-sm btn-outline-danger" @click="printReport('form1', null, true)">
                  <i class="bi bi-printer me-1"></i> (2 เท่า)
                </button>
              </div>

              <span class="fw-bold ms-3 me-1 text-success">หลักฐานเบิกจ่าย:</span>
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-success" @click="printReport('form2')">
                  <i class="bi bi-printer me-1"></i> พิมพ์
                </button>
                <button v-if="selectedMonth === 1 || selectedMonth === 4" class="btn btn-sm btn-outline-danger" @click="printReport('form2', null, true)">
                  <i class="bi bi-printer me-1"></i> (2 เท่า)
                </button>
              </div>

              <span class="fw-bold ms-3 me-1 text-warning text-dark">ตารางเวรปฏิบัติงาน:</span>
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-warning text-dark" @click="printReport('form3')">
                  <i class="bi bi-printer me-1"></i> พิมพ์
                </button>
                <button v-if="selectedMonth === 1 || selectedMonth === 4" class="btn btn-sm btn-outline-danger" @click="printReport('form3', null, true)">
                  <i class="bi bi-printer me-1"></i> (2 เท่า)
                </button>
              </div>

              <span class="fw-bold ms-3 me-1 text-info">บันทึกข้อความ:</span>
              <div class="btn-group me-3">
                <button class="btn btn-sm btn-info text-white shadow-sm" @click="printReport('memoDuty')">
                  <i class="bi bi-file-text me-1"></i> ขอขึ้นเวร
                </button>
                <button v-if="selectedMonth === 1 || selectedMonth === 4" class="btn btn-sm btn-danger text-white shadow-sm" @click="printReport('memoDuty', null, true)">
                  <i class="bi bi-file-text me-1"></i> (2 เท่า)
                </button>
              </div>

              <span class="fw-bold me-1 text-secondary">รายงานผล:</span>
              <div class="dropdown d-inline-block">
                <button
                  class="btn btn-sm btn-secondary text-white shadow-sm dropdown-toggle"
                  type="button"
                  @click="showDropdown = !showDropdown"
                  @blur="hideDropdown"
                >
                  <i class="bi bi-clipboard-check me-1"></i> รายงานผลปฏิบัติงาน
                </button>
                <ul class="dropdown-menu" :class="{ show: showDropdown }">
                  <li>
                    <a class="dropdown-item" href="#" @click.prevent="printReport('form5')"
                      >พิมพ์ทุกคน</a
                    >
                  </li>
                  <li v-if="selectedMonth === 1 || selectedMonth === 4">
                    <a class="dropdown-item text-danger" href="#" @click.prevent="printReport('form5', null, true)"
                      >พิมพ์ทุกคน (2 เท่า)</a
                    >
                  </li>
                  <li><hr class="dropdown-divider" /></li>
                  <li v-for="emp in reportData" :key="'menu-' + emp.name">
                    <a
                      class="dropdown-item"
                      href="#"
                      @click.prevent="printReport('form5', emp.name)"
                    >
                      {{ emp.name }}
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5 d-print-none">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">กำลังดึงข้อมูลรายงาน...</p>
    </div>

    <!-- Form 1: ตารางการเวรขอขึ้นปฏิบัติงาน -->
    <div
      ref="form1"
      v-show="activePrint === 'form1' || activePrint === 'all' || !activePrint"
      class="print-page table-section bg-white shadow-sm mb-4 mx-auto pt-5 pb-4 px-4"
      :class="{
        'd-print-block': activePrint === 'form1' || activePrint === 'all',
        'd-none': activePrint && activePrint !== 'form1' && activePrint !== 'all',
        'page-break': activePrint === 'all'
      }"
    >
      <div class="text-center mb-1">
        <h5 class="fw-bold fs-16pt">
          ตารางการเวรขอขึ้นปฏิบัติงานนอกเวลาราชการ ห้องบัตร โรงพยาบาลชานุมาน
          <span v-if="isPrintDouble">(2 เท่า)</span>
        </h5>
        <h6 class="mt-1 fs-16pt">
          ประจำเดือน {{ monthNames[selectedMonth - 1] }} {{ selectedYear + 543 }}
        </h6>
      </div>

      <table
        class="table table-bordered border-dark table-sm report-table w-100 align-middle text-center"
        style="font-size: 13pt"
      >
        <thead>
          <tr style="font-size: 12pt">
            <th rowspan="2" class="align-middle" style="width: 30px">ลำดับ</th>
            <th rowspan="2" class="align-middle text-nowrap" style="width: 130px">ชื่อ-สกุล</th>
            <th rowspan="2" class="align-middle text-nowrap" style="width: 110px">ตำแหน่ง</th>
            <th :colspan="daysInMonth" class="text-center">วันที่ปฏิบัติงานล่วงเวลา</th>
            <th rowspan="2" class="align-middle" style="width: 35px; line-height: 1;">รวม<br />วัน</th>
            <th rowspan="2" class="align-middle" style="width: 50px">หยุด</th>
            <th rowspan="2" class="align-middle" style="width: 50px">หมายเหตุ</th>
          </tr>
          <tr>
            <th
              v-for="d in daysInMonth"
              :key="d"
              style="width: 20px; font-size: 12pt; padding: 0"
              :class="{ 'bg-holiday': monthDaysInfo[d] === 'O' }"
            >
              {{ d }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(emp, index) in reportData" :key="emp.id">
            <td>{{ index + 1 }}</td>
            <td class="text-start px-2 text-nowrap">{{ emp.name }}</td>
            <td class="text-start px-2 text-nowrap">{{ emp.position }}</td>
            <td
              v-for="d in daysInMonth"
              :key="d"
              :class="{ 'bg-holiday': monthDaysInfo[d] === 'O' }"
              style="font-size: 14pt; padding: 0"
            >
              {{ emp.duties[d] === 'OPDCARD' ? 'X' : '' }}
            </td>
            <td>{{ emp.total_days }}</td>
            <td></td>
            <td></td>
          </tr>
        </tbody>
      </table>

      <!-- Signatures -->
      <div class="row mt-0 pt-0 signatures-section fs-16pt">
        <div class="col-6 text-center">
          <div class="signature-block">
            <div class="mb-1">
              (ลงชื่อ)........................................................................หัวหน้าผู้ควบคุม
            </div>
            <div class="mb-1 fw-bold">(นายศราวุฒิ แสนโท )</div>
            <div>นักวิชาการคอมพิวเตอร์ชำนาญการ</div>
            <div>หัวหน้ากลุ่มงานสุขภาพดิจิทัล</div>
          </div>
        </div>
        <div class="col-6 text-center">
          <div class="signature-block">
            <div class="mb-1">
              (ลงชื่อ)........................................................................ผู้อนุมัติจ่ายเงิน
            </div>
            <div>(นายธนากร คนเพียร)</div>
            <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
            <div>ผู้อำนวยการโรงพยาบาลชานุมาน</div>
          </div>
        </div>
      </div>
      <div class="mt-0 ms-5" style="font-size: 16pt">
        <span>หมายเหตุ :</span>
        <span class="ms-4">X</span>
        <span class="ms-4">ปฏิบัติงานนอกเวลาราชการ วันหยุดราชการ วันหยุดนักขัตฤกษ์</span>
      </div>
    </div>

    <!-- Form 2: หลักฐานการเบิกจ่าย -->
    <div
      ref="form2"
      v-show="activePrint === 'form2' || activePrint === 'all' || !activePrint"
      class="print-page table-section bg-white shadow-sm mb-4 mx-auto pt-5 pb-4 px-4"
      :class="{
        'd-print-block': activePrint === 'form2' || activePrint === 'all',
        'd-none': activePrint && activePrint !== 'form2' && activePrint !== 'all',
        'page-break': activePrint === 'all'
      }"
    >
      <div class="text-center mb-1">
        <h5 class="fw-bold fs-16pt">
          หลักฐานการเบิกจ่ายเงินค่าตอบแทนนอกเวลาราชการ ห้องบัตร โรงพยาบาลชานุมาน
          <span v-if="isPrintDouble">(2 เท่า)</span>
        </h5>
        <h6 class="mt-1 fs-16pt">
          ชื่อส่วนราชการ โรงพยาบาลชานุมาน จังหวัดอำนาจเจริญ ประจำเดือน
          {{ monthNames[selectedMonth - 1] }} {{ selectedYear + 543 }}
        </h6>
        <div class="d-flex justify-content-center mt-1 gap-2 fs-16pt">
          <span
            >ใบสำคัญที่............................................./.............................................</span
          >
          <span>ลงวันที่.............................................</span>
        </div>
      </div>

      <table
        class="table table-bordered border-dark table-sm report-table align-middle text-center"
        style="font-size: 13pt"
      >
        <thead>
          <tr style="font-size: 12pt">
            <th rowspan="2" class="align-middle" style="width: 30px">ลำดับ</th>
            <th rowspan="2" class="align-middle text-nowrap" style="width: 130px">ชื่อ-สกุล</th>
            <th rowspan="2" class="align-middle text-nowrap" style="width: 110px">ตำแหน่ง</th>
            <th rowspan="2" class="align-middle" style="width: 45px; font-size: 10pt; line-height: 1;">
              อัตรา<br />ค่าตอบแทน
            </th>
            <th :colspan="daysInMonth" class="text-center">วันที่ปฏิบัติงานล่วงเวลา</th>
            <th rowspan="2" class="align-middle" style="width: 35px; font-size: 10pt; line-height: 1;">
              รวม<br />วัน
            </th>
            <th rowspan="2" class="align-middle" style="width: 50px; font-size: 10pt; line-height: 1;">
              จำนวน<br />เงิน
            </th>
            <th rowspan="2" class="align-middle" style="width: 50px">รวม</th>
            <th rowspan="2" class="align-middle text-nowrap" style="width: 40px; font-size: 10pt;">
              ว/ด/ป
            </th>
            <th rowspan="2" class="align-middle text-nowrap" style="width: 60px; font-size: 10pt; line-height: 1;">
              ลายมือชื่อ<br />ผู้รับเงิน
            </th>
          </tr>
          <tr>
            <th
              v-for="d in daysInMonth"
              :key="d"
              style="width: 20px; font-size: 12pt; padding: 0"
              :class="{ 'bg-holiday': monthDaysInfo[d] === 'O' }"
            >
              {{ d }}
            </th>
          </tr>
        </thead>
        <tbody>
          <!-- Iterate per user -->
          <template v-for="(emp, index) in filteredReportData" :key="emp.id">
            <tr v-for="(breakdown, bIdx) in emp.rate_breakdowns" :key="emp.id + '-' + bIdx">
                <!-- Index, Name, Position only on first row of the employee -->
                <td v-if="bIdx === 0" :rowspan="emp.rate_breakdowns.length">
                  {{ index + 1 }}
                </td>
                <td
                  v-if="bIdx === 0"
                  :rowspan="emp.rate_breakdowns.length"
                  class="text-start px-2 text-nowrap"
                >
                  {{ emp.name }}
                </td>
                <td
                  v-if="bIdx === 0"
                  :rowspan="emp.rate_breakdowns.length"
                  class="text-start px-2 text-nowrap"
                >
                  {{ emp.position }}
                </td>
                
                <!-- Rate Details -->
                <td class="text-end px-1">{{ breakdown.rate }}</td>
                <td
                  v-for="d in daysInMonth"
                  :key="'cell-' + d"
                  :class="{ 'bg-holiday': monthDaysInfo[d] === 'O' }"
                  style="padding: 0"
                >
                  <template v-if="emp.duties[d] === 'OPDCARD' && (breakdown.is_special ? (emp.duties_rate_double && emp.duties_rate_double[d] == breakdown.rate) : (emp.duties_rate_normal ? emp.duties_rate_normal[d] == breakdown.rate : emp.duties_rate[d] == breakdown.rate))">
                     {{ monthDaysInfo[d] }}
                  </template>
                </td>
                <td>{{ breakdown.total_days }}</td>
                <td class="text-end px-1">
                  {{ breakdown.total_amount > 0 ? breakdown.total_amount.toLocaleString() : '-' }}
                </td>

                <!-- Total Amount and Empty Cells only on first row of the employee -->
                <td
                  v-if="bIdx === 0"
                  :rowspan="emp.rate_breakdowns.length"
                  class="align-middle text-end px-1"
                >
                  {{ emp.total_amount > 0 ? emp.total_amount.toLocaleString() : '-' }}
                </td>
                <td v-if="bIdx === 0" :rowspan="emp.rate_breakdowns.length"></td>
                <td v-if="bIdx === 0" :rowspan="emp.rate_breakdowns.length"></td>
            </tr>
          </template>
          <!-- Footer Row: Total -->
          <tr class="fw-bold">
            <td colspan="4" class="text-center">รวม</td>
            <td :colspan="daysInMonth"></td>
            <td>{{ totalDaysAll }}</td>
            <td class="text-end px-1">{{ totalAmountAll.toLocaleString() }}</td>
            <td></td>
            <td></td>
            <td></td>
          </tr>
        </tbody>
      </table>

      <!-- Footer Text Amount & Signatures -->
      <div class="d-flex mt-0 ps-4 fs-16pt">
        <div style="width: 200px">จำนวนเงิน (ตัวอักษร)</div>
        <div class="fw-bold">{{ thaiBahtText(totalAmountAll) }}</div>
      </div>

      <div class="mt-0 ps-4 fs-16pt">
        ขอรับรองว่าผู้ที่ได้รับเงินค่าตอบแทนการปฏิบัติงานนอกเวลาดังกล่าวได้ปฏิบัติงานนอกเวลาจริง
      </div>

      <!-- Signatures -->
      <div class="row mt-0 pt-0 signatures-section fs-16pt">
        <div class="col-6 text-center">
          <div class="signature-block">
            <div class="mb-1">
              (ลงชื่อ)........................................................................หัวหน้าผู้ควบคุม
            </div>
            <div>(นายศราวุฒิ แสนโท)</div>
            <div>นักวิชาการคอมพิวเตอร์ชำนาญการ</div>
            <div>หัวหน้ากลุ่มงานสุขภาพดิจิทัล</div>
          </div>
        </div>
        <div class="col-6 text-center">
          <div class="signature-block">
            <div class="mb-1">
              ลงชื่อ..........................................................ผู้จ่ายเงิน................/................/................
            </div>
            <div class="mb-1">
              (ลงชื่อ)........................................................................ผู้อนุมัติจ่ายเงิน
            </div>
            <div class="fw-bold">(นายธนากร คนเพียร)</div>
            <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
            <div>ผู้อำนวยการโรงพยาบาลชานุมาน</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Form 3: ตารางเวรปฏิบัติงาน -->
    <div
      ref="form3"
      v-show="activePrint === 'form3' || activePrint === 'all' || !activePrint"
      class="print-page table-section bg-white shadow-sm mx-auto pt-5 pb-4 px-4 mb-4"
      :class="{
        'd-print-block': activePrint === 'form3' || activePrint === 'all',
        'd-none': activePrint && activePrint !== 'form3' && activePrint !== 'all',
        'page-break': activePrint === 'all'
      }"
    >
      <div class="text-center mb-1">
        <h5 class="fw-bold fs-16pt">
          ตารางการเวรปฏิบัติงานนอกเวลาราชการ ห้องบัตร โรงพยาบาลชานุมาน
          <span v-if="isPrintDouble">(2 เท่า)</span>
        </h5>
        <h6 class="mt-1 fs-16pt">
          ประจำเดือน {{ monthNames[selectedMonth - 1] }} {{ selectedYear + 543 }}
        </h6>
      </div>

      <table
        class="table table-bordered border-dark table-sm report-table align-middle text-center"
        style="font-size: 13pt"
      >
        <thead>
          <tr style="font-size: 12pt">
            <th rowspan="2" class="align-middle" style="width: 30px">ลำดับ</th>
            <th rowspan="2" class="align-middle text-nowrap" style="width: 130px">ชื่อ-สกุล</th>
            <th rowspan="2" class="align-middle text-nowrap" style="width: 110px">ตำแหน่ง</th>
            <th :colspan="daysInMonth" class="text-center">วันที่ปฏิบัติงานล่วงเวลา</th>
            <th rowspan="2" class="align-middle" style="width: 35px; line-height: 1;">รวม<br />วัน</th>
            <th rowspan="2" class="align-middle" style="width: 50px">หยุด</th>
            <th rowspan="2" class="align-middle" style="width: 50px">หมายเหตุ</th>
          </tr>
          <tr>
            <th
              v-for="d in daysInMonth"
              :key="d"
              style="width: 20px; font-size: 12pt; padding: 0"
              :class="{ 'bg-holiday': monthDaysInfo[d] === 'O' }"
            >
              {{ d }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(emp, index) in filteredReportData" :key="emp.id">
            <td>{{ index + 1 }}</td>
            <td class="text-start px-2 text-nowrap">{{ emp.name }}</td>
            <td class="text-start px-2 text-nowrap">{{ emp.position }}</td>
            <td
              v-for="d in daysInMonth"
              :key="d"
              :class="{ 'bg-holiday': monthDaysInfo[d] === 'O' }"
              style="font-size: 14pt; padding: 0"
            >
              <template v-if="emp.duties[d] === 'OPDCARD'">
                <!-- Show O for Holiday, X for Weekday -->
                {{ emp.shift_type[d] }}
              </template>
            </td>
            <td>{{ emp.total_days }}</td>
            <td></td>
            <td></td>
          </tr>
        </tbody>
      </table>

      <!-- Signatures -->
      <div class="row mt-0 pt-0 signatures-section fs-16pt">
        <div class="col-6 text-center">
          <div class="signature-block">
            <div class="mb-1">
              (ลงชื่อ)........................................................................หัวหน้าผู้ควบคุม
            </div>
            <div>(นายศราวุฒิ แสนโท)</div>
            <div>นักวิชาการคอมพิวเตอร์ชำนาญการ</div>
            <div>หัวหน้ากลุ่มงานสุขภาพดิจิทัล</div>
          </div>
        </div>
        <div class="col-6 text-center">
          <div class="signature-block">
            <div class="mb-1">
              (ลงชื่อ)........................................................................ผู้อนุมัติจ่ายเงิน
            </div>
            <div>(นายธนากร คนเพียร)</div>
            <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
            <div>ผู้อำนวยการโรงพยาบาลชานุมาน</div>
          </div>
        </div>
      </div>
      <div class="mt-0 ms-5" style="font-size: 16pt">
        <div class="row">
          <div class="col-2">หมายเหตุ :</div>
          <div class="col-10">
            <div class="mb-2">
              <span class="d-inline-block text-center" style="width: 30px">O</span>
              ปฏิบัติงานนอกเวลาราชการ วันหยุดราชการ วันหยุดนักขัตฤกษ์ ตั้งแต่เวลา 08.00 - 16.00 น.
            </div>
            <div>
              <span class="d-inline-block text-center" style="width: 30px">X</span>
              ปฏิบัติงานนอกเวลาราชการ ในวันราชการ ตั้งแต่เวลา 16.00 - 20.00 น.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Form 4: บันทึกข้อความขออนุมัติปฏิบัติงานนอกเวลาราชการ -->
    <div
      ref="memoDuty"
      v-show="activePrint === 'memoDuty' || activePrint === 'all' || !activePrint"
      class="print-page memo-section portrait-section bg-white shadow-sm mb-4 mx-auto pt-5 pb-4 px-4"
      :class="{
        'd-print-block': activePrint === 'memoDuty' || activePrint === 'all',
        'd-none': activePrint && activePrint !== 'memoDuty' && activePrint !== 'all',
        'page-break': activePrint === 'all'
      }"
    >
      <div class="memo-header d-flex align-items-center mb-1">
        <div class="garuda-wrapper">
          <img
            src="../../assets/krut.png"
            alt="Garuda"
            class="garuda-img"
            @error="$event.target.style.display = 'none'"
          />
        </div>
        <div class="memo-title-wrapper flex-grow-1 text-center">
          <h1 class="memo-title">บันทึกข้อความ</h1>
        </div>
      </div>

      <div class="memo-metadata">
        <div class="d-flex align-items-baseline mb-0">
          <span class="fw-bold fs-20pt label-agency">ส่วนราชการ</span>
          <span class="fs-16pt">กลุ่มงานสุขภาพดิจิทัล โรงพยาบาลชานุมาน</span>
        </div>

        <div class="row g-0 align-items-baseline">
          <div class="col-6 d-flex align-items-baseline">
            <span class="fw-bold fs-20pt label-ref">ที่</span>
            <span class="fs-16pt">อจ.0033.314/</span>
          </div>
          <div class="col-6 d-flex align-items-baseline">
            <span class="fw-bold fs-20pt label-date">วันที่</span>
            <span class="fs-16pt">{{ getPreDutyDate() }}</span>
          </div>
        </div>

        <div class="d-flex align-items-baseline mb-0">
          <span class="fw-bold fs-20pt label-subject">เรื่อง</span>
          <span class="fs-16pt">ขออนุมัติปฏิบัติงานนอกเวลาราชการ (เวรห้องบัตร)</span>
        </div>

        <div class="d-flex align-items-baseline mb-3">
          <span class="fw-bold fs-18pt label-to">เรียน</span>
          <span class="fs-16pt">ผู้อำนวยการโรงพยาบาลชานุมาน</span>
        </div>
      </div>

      <div class="memo-body fs-16pt">
        <div class="content-paragraph text-justify">
          ด้วยกลุ่มงานสุขภาพดิจิทัล โรงพยาบาลชานุมาน
          มีความจำเป็นต้องขึ้นปฏิบัติงานนอกเวลาราชการประจำห้องบัตร ตามนโยบายเร่งด่วน และขอ Authen
          code ตรวจสอบความถูกต้องของข้อมูล เพื่อให้การดำเนินงานในการส่งเบิกค่ารักษาพยาบาลให้ทันเวลา
        </div>
        <div class="content-paragraph text-justify">
          ในการนี้ กลุ่มงานสุขภาพดิจิทัล จึงขออนุมัติให้เจ้าหน้าที่ปฏิบัติงานนอกเวลาราชการ
          ประจำเดือน{{ monthNames[selectedMonth - 1] }} {{ selectedYear + 543 }} ดังรายนามต่อไปนี้
        </div>

        <div class="ms-5 mb-2">
          <div v-for="(emp, idx) in filteredReportData" :key="emp.name" class="d-flex">
            <span style="width: 25px">{{ idx + 1 }}.</span>
            <span style="width: 180px">{{ emp.name }}</span>
            <span>ตำแหน่ง {{ emp.position }}</span>
          </div>
        </div>

        <div class="content-paragraph mb-1">จึงเรียนมาเพื่อโปรดทราบและพิจารณาอนุมัติ</div>
        <div class="memo-signatures">
          <div class="signature-block ms-auto text-center mt-0" style="width: 55%">
            <br>
            <div class="mb-1">(นายศราวุฒิ แสนโท)</div>
            <div>นักวิชาการคอมพิวเตอร์ชำนาญการ</div>
            <div>หัวหน้ากลุ่มงานสุขภาพดิจิทัล</div>
          </div>

          <div class="signature-block mt-1">
            <div class="mb-1">เรียน ผู้อำนวยการโรงพยาบาลชานุมาน</div>
            <div class="ps-5">- ได้ตรวจสอบถูกต้องแล้ว</div>
            <div class="ps-5 mb-1">- เห็นควรอนุมัติ</div>
            <div class="signature-block text-center mt-2 text-nowrap" style="width: 30%">
              <br>
              <div class="mb-1">(นายธนากร คนเพียร)</div>
              <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
              <div>หัวหน้ากลุ่มงานบริหารทั่วไป</div>
            </div>
          </div>

          <!-- Signature 3: Approval -->
          <div class="signature-block mt-1 pt-1">
            <div class="signature-block ms-auto text-center" style="width: 55%">
              <div class="mb-1">อนุมัติ</div>
              <br>
              <div class="signature-line mb-1"></div>
              <div class="mb-1">(นายธนากร คนเพียร)</div>
              <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
              <div>ผู้อำนวยการโรงพยาบาลชานุมาน</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Form 5: แบบรายงานผลการปฏิบัติงานนอกเวลา (Per Employee) -->
    <div
      v-for="emp in filteredReportData"
      :key="'form5-' + emp.name"
      v-show="
        (activePrint === 'form5' && (!activePrintEmpName || activePrintEmpName === emp.name)) ||
        activePrint === 'all' ||
        !activePrint
      "
      class="print-page memo-section portrait-section bg-white shadow-sm mb-4 mx-auto pt-4 pb-4 px-4"
      :class="{
        'd-print-block': (activePrint === 'form5' && (!activePrintEmpName || activePrintEmpName === emp.name)) || activePrint === 'all',
        'd-none': (activePrint === 'form5' && activePrintEmpName && activePrintEmpName !== emp.name) || (activePrint && activePrint !== 'form5' && activePrint !== 'all'),
        'page-break': activePrint === 'all' || (activePrint === 'form5' && !activePrintEmpName)
      }"
    >
      <div class="text-center mb-3">
        <h5 class="fw-bold mb-1 fs-16pt">แบบรายงานผลการปฏิบัติงานนอกเวลา</h5>
        <h6 class="fw-normal fs-16pt">
          ประจำเดือน {{ monthNames[selectedMonth - 1] }} {{ selectedYear + 543 }}
        </h6>
      </div>

      <div class="mb-3 fs-16pt">
        <div class="row">
          <div class="col-6">ชื่อ-นามสกุล {{ emp.name }}</div>
          <div class="col-6">ตำแหน่ง {{ emp.position }}</div>
        </div>
        <div>จุดปฏิบัติงาน กลุ่มงานสุขภาพดิจิทัล (ห้องบัตร)</div>
      </div>

      <table
        class="table table-bordered border-dark table-sm report-table align-middle text-center"
        style="font-size: 14pt"
      >
        <thead>
          <tr>
            <th class="text-nowrap" style="width: 12%">วันที่ปฏิบัติ</th>
            <th class="text-nowrap" style="width: 15%">เวลาปฏิบัติ</th>
            <th class="text-nowrap">ผลการปฏิบัติงาน</th>
            <th class="text-nowrap" style="width: 18%">ลายมือชื่อผู้ปฏิบัติ</th>
            <th class="text-nowrap" style="width: 18%">ลายมือชื่อผู้รับรอง</th>
            <th class="text-nowrap" style="width: 10%">หมายเหตุ</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="d in daysInMonth" :key="d">
            <tr v-if="emp.duties[d] === 'OPDCARD'" style="height: 35px">
              <td>{{ d }} {{ monthNames[selectedMonth - 1] }} {{ selectedYear + 543 }}</td>
              <td>{{ monthDaysInfo[d] === 'O' ? '08.00 - 16.00 น.' : '16.00 - 20.00 น.' }}</td>
              <td class="text-start ps-3">-ขึ้นเวรห้องบัตร</td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
          </template>
          <tr v-for="i in 5" :key="'empty-' + i" style="height: 35px">
            <td>&nbsp;</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import BAHTTEXT from 'thai-baht-text';

export default {
  name: 'OTReportOpdCardView',
  data() {
    const today = new Date();
    return {
      selectedMonth: today.getMonth() + 1,
      selectedYear: today.getFullYear(),
      currentYear: today.getFullYear(),
      monthNames: [
        'มกราคม',
        'กุมภาพันธ์',
        'มีนาคม',
        'เมษายน',
        'พฤษภาคม',
        'มิถุนายน',
        'กรกฎาคม',
        'สิงหาคม',
        'กันยายน',
        'ตุลาคม',
        'พฤศจิกายน',
        'ธันวาคม'
      ],
      isLoading: false,
      reportData: [],
      daysInMonth: 31,
      monthDaysInfo: {},
      activePrint: null,
      activePrintEmpName: null,
      showDropdown: false,
      isPrintDouble: false
    };
  },
  computed: {
    filteredReportData() {
      if (this.isPrintDouble === null && this.activePrint === null) return this.reportData;
      
      const needFilter = (this.selectedMonth === 1 || this.selectedMonth === 4);
      if (!needFilter && !this.isPrintDouble) return this.reportData;

      return this.reportData.map(emp => {
        let newEmp = JSON.parse(JSON.stringify(emp));
        
        let tDays = 0;
        for (let d = 1; d <= 31; d++) {
           if (newEmp.duties[d]) {
               const rate = newEmp.duties_rate[d] || 0;
               // ใช้ flag is_special ที่ส่งมาจาก backend
               const isDoubleRate = newEmp.duties_rate_double && newEmp.duties_rate_double[d] > 0;
               const isNormalRate = newEmp.duties_rate_normal && newEmp.duties_rate_normal[d] > 0;
               
               if (this.isPrintDouble && !isDoubleRate) {
                   newEmp.duties[d] = '';
                   newEmp.shift_type[d] = '';
               } else if (!this.isPrintDouble && needFilter && !isNormalRate) {
                   newEmp.duties[d] = '';
                   newEmp.shift_type[d] = '';
               } else {
                   tDays++;
               }
           }
        }
        newEmp.total_days = tDays;
        
        if (this.isPrintDouble) {
             newEmp.rate_breakdowns = newEmp.rate_breakdowns.filter(b => b.is_special === true);
        } else if (needFilter) {
             newEmp.rate_breakdowns = newEmp.rate_breakdowns.filter(b => b.is_special !== true);
        }
        
        newEmp.total_amount = newEmp.rate_breakdowns.reduce((sum, b) => sum + b.total_amount, 0);
        
        return newEmp;
      }).filter(emp => emp.total_days > 0 || (emp.rate_breakdowns && emp.rate_breakdowns.length > 0));
    },
    totalDaysAll() {
      return this.filteredReportData.reduce((sum, emp) => sum + emp.total_days, 0);
    },
    totalAmountAll() {
      return this.filteredReportData.reduce((sum, emp) => sum + emp.total_amount, 0);
    }
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      this.isLoading = true;
      try {
        const response = await axios.get('/api-digital/duties_opdcard/get-ot-report-opdcard.php', {
          params: { month: this.selectedMonth, year: this.selectedYear }
        });

        if (response.data.status === 'success') {
          this.reportData = response.data.data
            .filter((emp) => emp.total_days > 0)
            .sort((a, b) => {
              const nameOrder = ['ลัดดา', 'ศราวุฒิ', 'สุริยา', 'ธีระพงษ์', 'ยุทธชัย', 'ศิริลักษณ์'];
              const indexA = nameOrder.findIndex((name) => a.name.includes(name));
              const indexB = nameOrder.findIndex((name) => b.name.includes(name));
              if (indexA !== -1 && indexB !== -1) return indexA - indexB;
              if (indexA !== -1) return -1;
              if (indexB !== -1) return 1;
              return a.name.localeCompare(b.name, 'th');
            });
          this.daysInMonth = response.data.daysInMonth;
          this.monthDaysInfo = response.data.monthDaysInfo;
        } else {
          console.error(response.data.error);
        }
      } catch (error) {
        console.error('Error fetching OT report data:', error);
      } finally {
        this.isLoading = false;
      }
    },
    printReport(formName, empName = null, isDouble = false) {
      this.isPrintDouble = isDouble;
      this.activePrint = formName;
      this.activePrintEmpName = empName;
      this.$nextTick(() => {
        window.print();
        setTimeout(() => {
          this.activePrint = null;
          this.activePrintEmpName = null;
          this.isPrintDouble = false;
        }, 500);
      });
    },
    thaiBahtText(amount) {
      try {
        if (typeof BAHTTEXT === 'function') {
          return BAHTTEXT(amount);
        }
        return amount.toLocaleString() + ' บาทถ้วน';
      } catch (e) {
        return amount.toLocaleString() + ' บาทถ้วน';
      }
    },
    getPreDutyDate() {
      // Last day of previous month
      const d = new Date(this.selectedYear, this.selectedMonth - 1, 0);
      return `${d.getDate()} ${this.monthNames[d.getMonth()]} ${d.getFullYear() + 543}`;
    },
    hideDropdown() {
      setTimeout(() => {
        this.showDropdown = false;
      }, 200);
    }
  }
};
</script>

<style scoped>
@font-face {
  font-family: 'TH Sarabun New';
  src: url('/fonts/THSarabun.ttf') format('truetype');
  font-weight: normal;
  font-style: normal;
}
@font-face {
  font-family: 'TH Sarabun New';
  src: url('/fonts/THSarabun Bold.ttf') format('truetype');
  font-weight: bold;
  font-style: normal;
}

.report-container {
  font-family: 'TH Sarabun New', 'Sarabun', sans-serif;
  color: #000;
  font-size: 16pt;
  line-height: 1.1;
}

.print-page {
  padding-left: 15mm !important;
  padding-right: 15mm !important;
  padding-top: 10mm !important;
}

.text-purple {
  color: #6f42c1;
}

/* Font Size Utilities */
.fs-14pt {
  font-size: 14pt !important;
}
.fs-16pt {
  font-size: 16pt !important;
}
.fs-20pt {
  font-size: 20pt !important;
}
.fs-29pt {
  font-size: 29pt !important;
}

.table th,
.table td {
  padding: 1px 2px !important;
  font-size: 14pt !important;
}

.bg-holiday {
  background-color: #d9d9d9 !important;
  -webkit-print-color-adjust: exact !important;
  print-color-adjust: exact !important;
}

.report-table {
  border: 1px solid #000;
}
.report-table th,
.report-table td {
  border: 1px solid #000 !important;
}

/* Memo Section Styles */
.memo-title {
  font-size: 29pt !important;
  font-weight: bold !important;
  margin: 0 !important;
}

.memo-metadata {
  margin-bottom: 2mm;
}

.memo-header {
  width: 100%;
}

.label-agency {
  width: 25mm;
}
.label-ref {
  width: 10mm;
}
.label-date {
  width: 15mm;
}
.label-subject {
  width: 15mm;
}
.label-to {
  width: 15mm;
}

.memo-body {
  line-height: 1.15;
}

.content-paragraph {
  text-indent: 25mm;
  margin-bottom: 2mm;
}

.garuda-wrapper {
  margin-right: -1.5cm; /* Push the title visually to center by compensating for image width */
}

.garuda-img {
  height: 2cm; /* กำหนดความสูงตราครุฑ (มาตรฐาน: 1.5cm สำหรับบันทึกข้อความภายใน / 3cm สำหรับภายนอก) */
  width: auto;
}

/* Signatures */
.signature-block {
  line-height: 1.1;
}

.portrait-section {
  width: 210mm;
  min-height: 297mm;
}

.landscape-section {
  width: 297mm;
  min-height: 210mm;
}

@media print {
  @page {
    margin: 0;
  }

  @page landscape-page {
    size: A4 landscape;
    margin: 15mm 15mm 15mm 15mm;
  }

  @page portrait-page {
    size: A4 portrait;
    margin: 0;
  }

  html, body {
    background: none !important;
    padding: 0 !important;
    margin: 0 !important;
    height: auto !important;
    min-height: 0 !important;
  }

  .report-container, .report-container.min-vh-100.pb-5.pt-3 {
    padding: 0 !important;
    margin: 0 !important;
    min-height: 0 !important;
    background: none !important;
  }

  .print-page {
    margin: 0 !important;
    padding: 0 !important;
    box-shadow: none !important;
    width: 100% !important;
    color: #000;
  }

  /* Specific form section layouts */
  .table-section {
    page: landscape-page;
    width: 100% !important;
    box-sizing: border-box !important;
    padding: 10mm 15mm !important;
    min-height: 0 !important;
    height: auto !important;
  }

  .report-table {
    font-size: 16pt !important;
  }
  
  .report-table th, .report-table td {
    padding: 0 !important;
    line-height: 1.2 !important;
  }

  .table-section .fs-16pt {
    font-size: 16pt !important;
  }

  .table-section .fs-14pt {
    font-size: 16pt !important;
  }

  .table-section .signatures-section {
    font-size: 16pt !important;
    margin-top: 10px !important;
  }

  .pt-5 {
    padding-top: 1rem !important;
  }

  .memo-section {
    page: portrait-page;
    width: 100% !important;
    box-sizing: border-box !important;
    padding: 5mm 20mm 15mm 30mm !important;
    min-height: 0 !important;
    height: auto !important;
  }

  .portrait-section {
    page: portrait-page;
    width: 100% !important;
    box-sizing: border-box !important;
    padding: 5mm 20mm 15mm 30mm !important;
    min-height: 0 !important;
    height: auto !important;
  }

  body * {
    visibility: hidden;
  }

  .print-page,
  .print-page * {
    visibility: visible !important;
  }

  .signatures-section {
    page-break-inside: avoid;
  }

  .d-print-none {
    display: none !important;
  }

  .d-print-block {
    display: block !important;
  }

  .page-break {
    page-break-after: always;
  }
}
</style>
