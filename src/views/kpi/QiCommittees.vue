<template>
  <div class="container-fluid mt-5 px-4">
    <div class="card calm-card mb-4">
      <div class="card-header calm-bg-lavender calm-text-navy py-3 d-flex justify-content-between align-items-center border-bottom-0">
        <h4 class="mb-0 fw-bold">คณะกรรมการพัฒนาคุณภาพโรงพยาบาล (HA)</h4>
        <div>
          <button class="btn btn-warning rounded-pill px-3 fw-bold me-2" @click="$router.push('/qi-dashboard')">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard ความก้าวหน้า
          </button>
          <button class="btn btn-danger rounded-pill px-3 fw-bold me-2" @click="generatePdf" :disabled="generatingPdf">
            <span v-if="generatingPdf" class="spinner-border spinner-border-sm me-1"></span>
            <i class="bi bi-file-earmark-pdf-fill me-1" v-else></i> สร้างคำสั่งแต่งตั้ง (PDF)
          </button>
          <button class="btn btn-secondary rounded-pill px-3 fw-bold" @click="$router.push('/home-backoffice')">
            <i class="bi bi-house-fill me-1"></i> กลับหน้าหลัก
          </button>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Sidebar / Tabs for Committees -->
      <div class="col-md-2 mb-4">
        <div class="list-group calm-card shadow-sm">
          <button 
            v-for="team in committees" 
            :key="team.id"
            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3"
            :class="{ 'active fw-bold calm-bg-navy border-0': selectedTeam && selectedTeam.id === team.id }"
            @click="selectTeam(team)"
          >
            <span>
              <i class="bi bi-people-fill me-2" :class="selectedTeam && selectedTeam.id === team.id ? 'text-white' : 'text-primary'"></i>
              {{ team.name }}
            </span>
            <span class="badge rounded-pill" :class="selectedTeam && selectedTeam.id === team.id ? 'bg-light text-dark' : 'bg-primary'">
              {{ team.member_count }}
            </span>
          </button>
        </div>
      </div>

      <!-- Main Content for Selected Committee -->
      <div class="col-md-10">
        <div class="card calm-card shadow-sm h-100" v-if="selectedTeam">
          <div class="card-header bg-white py-2 border-bottom">
            <ul class="nav nav-tabs card-header-tabs m-0">
              <li class="nav-item">
                <a class="nav-link fw-bold" :class="{ 'active text-primary': activeTab === 'members', 'text-muted': activeTab !== 'members' }" href="#" @click.prevent="activeTab = 'members'">
                  <i class="bi bi-people-fill me-1"></i> รายชื่อคณะกรรมการ
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link fw-bold" :class="{ 'active text-primary': activeTab === 'plans', 'text-muted': activeTab !== 'plans' }" href="#" @click.prevent="activeTab = 'plans'">
                  <i class="bi bi-journal-text me-1"></i> แผนพัฒนาคุณภาพ (ข้อเสนอแนะ)
                </a>
              </li>
            </ul>
          </div>
          
          <!-- Members Tab -->
          <div v-if="activeTab === 'members'" class="d-flex flex-column h-100">
            <div class="p-3 d-flex justify-content-between align-items-center border-bottom">
              <h5 class="mb-0 fw-bold text-primary">ทีม: {{ selectedTeam.name }}</h5>
              <button class="btn btn-success fw-bold rounded-pill px-3" @click="openAddModal">
                <i class="bi bi-person-plus-fill me-1"></i> เพิ่มรายชื่อ
              </button>
            </div>
            <div class="card-body p-0 border-bottom">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="calm-bg-lavender calm-text-navy">
                    <tr>
                      <th class="ps-4 py-3" style="width: 50px;">#</th>
                      <th class="py-3">ชื่อ-นามสกุล</th>
                      <th class="py-3">บทบาท</th>
                      <th class="py-3 text-end pe-4">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="members.length === 0">
                      <td colspan="4" class="text-center py-5 text-muted">
                        <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                        ยังไม่มีรายชื่อในทีมนี้
                      </td>
                    </tr>
                    <tr v-for="(member, index) in members" :key="member.id">
                      <td class="ps-4">{{ index + 1 }}</td>
                      <td class="fw-bold">{{ member.officer_name }}</td>
                      <td>
                        <span class="badge" :class="getRoleBadgeClass(member.role)">
                          {{ member.role }}
                        </span>
                      </td>
                      <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-warning me-2" @click="openEditModal(member)">
                          <i class="bi bi-pencil-square"></i> แก้ไข
                        </button>
                        <button class="btn btn-sm btn-outline-danger" @click="removeMember(member.id, member.officer_name)">
                          <i class="bi bi-trash-fill"></i> ลบ
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            
            <div class="card-body p-4 bg-light mt-auto rounded-bottom">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark m-0"><i class="bi bi-card-text text-primary me-2"></i>บทบาทหน้าที่ (Roles and Responsibilities)</h6>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="openEditDescriptionModal">
                  <i class="bi bi-pencil-square"></i> แก้ไขบทบาทหน้าที่
                </button>
              </div>
              <div class="p-3 bg-white rounded border shadow-sm text-dark" style="white-space: pre-wrap; font-size: 0.95rem; min-height: 80px;" v-if="selectedTeam.description">
                {{ selectedTeam.description }}
              </div>
              <div class="p-3 bg-white rounded border shadow-sm text-muted fst-italic text-center py-4" v-else>
                ยังไม่มีข้อมูลบทบาทหน้าที่ของคณะกรรมการชุดนี้
              </div>
            </div>
          </div>

          <!-- Plans Tab -->
          <div v-if="activeTab === 'plans'" class="d-flex flex-column h-100">
            <div class="p-3 d-flex justify-content-between align-items-center border-bottom">
              <h5 class="mb-0 fw-bold text-primary">แผนพัฒนาคุณภาพตามข้อเสนอแนะ: {{ selectedTeam.name }}</h5>
              <button class="btn btn-success fw-bold rounded-pill px-3" @click="openPlanModal()">
                <i class="bi bi-plus-circle-fill me-1"></i> เพิ่มแผนพัฒนา
              </button>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive" style="max-height: 700px;">
                <table class="table table-bordered table-hover align-middle mb-0" style="min-width: 1500px; font-size: 0.85rem;">
                  <thead class="calm-bg-lavender calm-text-navy text-center align-middle sticky-top">
                    <tr>
                      <th rowspan="2" style="width: 40px;">#</th>
                      <th rowspan="2" style="width: 100px;">มาตรฐาน</th>
                      <th rowspan="2" style="width: 250px;">ข้อเสนอแนะ</th>
                      <th rowspan="2" style="width: 250px;">แผนพัฒนาคุณภาพ</th>
                      <th colspan="4">ระยะเวลาดำเนินการ</th>
                      <th rowspan="2" style="width: 150px;">ตัวชี้วัด</th>
                      <th rowspan="2" style="width: 100px;">เป้าหมาย</th>
                      <th rowspan="2" style="width: 120px;">ผู้รับผิดชอบ</th>
                      <th rowspan="2" style="width: 100px;">ระยะเวลาการติดตาม</th>
                      <th colspan="4">ผลการดำเนินงาน</th>
                      <th rowspan="2" style="width: 100px;">จัดการ</th>
                    </tr>
                    <tr>
                      <th style="width: 70px;">ปี 2568</th>
                      <th style="width: 70px;">ปี 2569</th>
                      <th style="width: 70px;">ปี 2570</th>
                      <th style="width: 70px;">ปี 2571</th>
                      <th style="width: 70px;">ปี 2568</th>
                      <th style="width: 70px;">ปี 2569</th>
                      <th style="width: 70px;">ปี 2570</th>
                      <th style="width: 70px;">ปี 2571</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="plans.length === 0">
                      <td colspan="17" class="text-center py-5 text-muted">
                        <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                        ยังไม่มีข้อมูลแผนพัฒนาคุณภาพ
                      </td>
                    </tr>
                    <tr v-for="(plan, index) in plans" :key="plan.id">
                      <td class="text-center">{{ index + 1 }}</td>
                      <td>{{ plan.standard }}</td>
                      <td style="white-space: pre-wrap; background-color: #fdf5e6;">{{ plan.recommendation }}</td>
                      <td style="white-space: pre-wrap;">{{ plan.plan }}</td>
                      <td class="text-center">{{ plan.period_2568 }}</td>
                      <td class="text-center">{{ plan.period_2569 }}</td>
                      <td class="text-center">{{ plan.period_2570 }}</td>
                      <td class="text-center">{{ plan.period_2571 }}</td>
                      <td>{{ plan.indicator }}</td>
                      <td class="text-center">{{ plan.target }}</td>
                      <td class="text-center">{{ plan.responsible }}</td>
                      <td class="text-center">{{ plan.monitoring_period }}</td>
                      <td class="text-center">{{ plan.result_2568 }}</td>
                      <td class="text-center">{{ plan.result_2569 }}</td>
                      <td class="text-center">{{ plan.result_2570 }}</td>
                      <td class="text-center">{{ plan.result_2571 }}</td>
                      <td class="text-center">
                        <button class="btn btn-sm btn-outline-warning mb-1 w-100" @click="openPlanModal(plan)">
                          <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger w-100" @click="deletePlan(plan.id)">
                          <i class="bi bi-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card calm-card shadow-sm h-100 d-flex align-items-center justify-content-center" style="min-height: 400px;" v-else>
          <div class="text-center text-muted">
            <i class="bi bi-arrow-left-circle fs-1 d-block mb-3 text-primary"></i>
            <h5>กรุณาเลือกทีมคณะกรรมการด้านซ้ายมือ</h5>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1" ref="addMemberModal">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content calm-card">
          <div class="modal-header calm-bg-lavender calm-text-navy border-0">
            <h5 class="modal-title fw-bold" v-if="!form.id">เพิ่มรายชื่อเข้าทีม {{ selectedTeam?.name }}</h5>
            <h5 class="modal-title fw-bold" v-else>แก้ไขบทบาท {{ form.officer_name }}</h5>
            <button type="button" class="btn-close" @click="closeAddModal"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="submitAddMember">
              <div class="mb-3 position-relative">
                <label class="form-label fw-bold">ค้นหารายชื่อเจ้าหน้าที่ <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  class="form-control" 
                  placeholder="พิมพ์ชื่อเพื่อค้นหา..." 
                  v-model="staffSearch"
                  @focus="!form.id ? showStaffDropdown = true : null"
                  :readonly="!!form.id"
                  required
                >
                <ul class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; max-height: 200px; overflow-y: auto;" v-if="showStaffDropdown && filteredStaff.length > 0">
                  <li 
                    class="list-group-item list-group-item-action" 
                    v-for="staff in filteredStaff" 
                    :key="staff.HR_CID"
                    @click="selectStaff(staff)"
                    style="cursor: pointer;"
                  >
                    {{ staff.FULLNAME }}
                  </li>
                </ul>
              </div>

              <div class="mb-4">
                <label class="form-label fw-bold">บทบาทหน้าที่</label>
                <select class="form-select" v-model="form.role">
                  <option v-for="role in roles" :key="role.id" :value="role.name">
                    {{ role.name }}
                  </option>
                </select>
              </div>

              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary fw-bold py-2" :disabled="!form.officer_name">
                  บันทึก
                </button>
                <button type="button" class="btn btn-light" @click="closeAddModal">
                  ยกเลิก
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Description Modal -->
    <div class="modal fade" id="editDescriptionModal" tabindex="-1" ref="editDescriptionModal">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content calm-card">
          <div class="modal-header calm-bg-lavender calm-text-navy border-0">
            <h5 class="modal-title fw-bold">แก้ไขบทบาทหน้าที่ {{ selectedTeam?.name }}</h5>
            <button type="button" class="btn-close" @click="closeEditDescriptionModal"></button>
          </div>
          <div class="modal-body p-4 bg-white">
            <form @submit.prevent="saveDescription">
              <div class="mb-3">
                <label class="form-label fw-bold text-dark mb-2">บทบาทหน้าที่ (Roles & Responsibilities)</label>
                <textarea class="form-control bg-light shadow-sm" rows="10" v-model="editDescriptionText" placeholder="ระบุบทบาทหน้าที่..."></textarea>
              </div>
              <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary fw-bold py-2 rounded-pill shadow-sm" :disabled="savingDescription">
                  <span v-if="savingDescription" class="spinner-border spinner-border-sm me-2"></span>
                  <i class="bi bi-save-fill me-1" v-else></i> บันทึกบทบาทหน้าที่
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Plan Modal (Add/Edit) -->
    <div class="modal fade" id="planModal" tabindex="-1" ref="planModal">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content calm-card">
          <div class="modal-header calm-bg-lavender calm-text-navy border-0">
            <h5 class="modal-title fw-bold" v-if="!planForm.id">เพิ่มแผนพัฒนาคุณภาพ {{ selectedTeam?.name }}</h5>
            <h5 class="modal-title fw-bold" v-else>แก้ไขแผนพัฒนาคุณภาพ</h5>
            <button type="button" class="btn-close" @click="closePlanModal"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="submitPlan">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label fw-bold">มาตรฐาน</label>
                  <input type="text" class="form-control" v-model="planForm.standard">
                </div>
                <div class="col-md-9">
                  <label class="form-label fw-bold">ตัวชี้วัด</label>
                  <input type="text" class="form-control" v-model="planForm.indicator">
                </div>
                
                <div class="col-md-6">
                  <label class="form-label fw-bold">ข้อเสนอแนะ</label>
                  <textarea class="form-control" rows="4" v-model="planForm.recommendation" style="background-color: #fdf5e6;"></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">แผนพัฒนาคุณภาพ</label>
                  <textarea class="form-control" rows="4" v-model="planForm.plan"></textarea>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-bold">เป้าหมาย</label>
                  <input type="text" class="form-control" v-model="planForm.target">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">ผู้รับผิดชอบ</label>
                  <input type="text" class="form-control" v-model="planForm.responsible">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">ระยะเวลาการติดตาม</label>
                  <input type="text" class="form-control" v-model="planForm.monitoring_period" placeholder="เช่น ทุก 1 เดือน">
                </div>
              </div>

              <hr class="my-4">
              <h6 class="fw-bold text-primary mb-3">ระยะเวลาดำเนินการ</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-3">
                  <label class="form-label small text-muted">ปี 2568</label>
                  <input type="text" class="form-control form-control-sm" v-model="planForm.period_2568" placeholder="เช่น พ.ค. - ก.ย. 68">
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-muted">ปี 2569</label>
                  <input type="text" class="form-control form-control-sm" v-model="planForm.period_2569">
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-muted">ปี 2570</label>
                  <input type="text" class="form-control form-control-sm" v-model="planForm.period_2570">
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-muted">ปี 2571</label>
                  <input type="text" class="form-control form-control-sm" v-model="planForm.period_2571">
                </div>
              </div>

              <h6 class="fw-bold text-success mb-3">ผลการดำเนินงาน</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-3">
                  <label class="form-label small text-muted">ปี 2568</label>
                  <input type="text" class="form-control form-control-sm" v-model="planForm.result_2568">
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-muted">ปี 2569</label>
                  <input type="text" class="form-control form-control-sm" v-model="planForm.result_2569">
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-muted">ปี 2570</label>
                  <input type="text" class="form-control form-control-sm" v-model="planForm.result_2570">
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-muted">ปี 2571</label>
                  <input type="text" class="form-control form-control-sm" v-model="planForm.result_2571">
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <button type="button" class="btn btn-light px-4 rounded-pill" @click="closePlanModal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold">
                  <i class="bi bi-save me-1"></i> บันทึกข้อมูล
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- PDF Preview Modal -->
    <div class="modal fade" id="pdfPreviewModal" tabindex="-1" ref="pdfPreviewModal">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content calm-card bg-light">
          <div class="modal-header calm-bg-navy text-white border-0">
            <h5 class="modal-title fw-bold"><i class="bi bi-search me-2"></i>ตัวอย่างก่อนพิมพ์ (Print Preview)</h5>
            <button type="button" class="btn-close btn-close-white" @click="closePdfPreview"></button>
          </div>
          <div class="modal-body p-4 d-flex justify-content-center bg-secondary" style="overflow-y: auto; max-height: 75vh;">
             <!-- The actual PDF container -->
             <div id="pdf-container" class="bg-white text-dark shadow-sm" style="width: 210mm; min-height: 297mm; font-family: 'Sarabun', sans-serif; font-size: 16px; color: #000; padding: 20mm; line-height: 1.5; box-sizing: border-box;">
                <!-- Page 1 Header -->
                <div class="text-center mb-4">
                  <img :src="krutImg" alt="Krut" style="height: 60px; width: 60px;" class="mb-2" />
                  <h5 class="fw-bold mb-1" style="font-size: 20px;">คำสั่งโรงพยาบาลชานุมาน</h5>
                  <h5 class="fw-bold mb-3" style="font-size: 20px;">ที่ ........ / ........</h5>
                  <h5 class="fw-bold" style="font-size: 20px;">เรื่อง แต่งตั้งคณะกรรมการพัฒนาคุณภาพโรงพยาบาล (HA) ปีงบประมาณ ๒๕๖๖</h5>
                </div>
                
                <div style="text-indent: 2.5em; text-align: justify; margin-bottom: 20px;">
                  ด้วย โรงพยาบาลชานุมาน มีความมุ่งมั่น ที่จะพัฒนาให้เป็นโรงพยาบาลที่ผ่านการรับรองคุณภาพ ตามมาตรฐานของสถาบันรับรองคุณภาพสถานพยาบาล (สรพ.) และให้มีการพัฒนางานบริการให้มีคุณภาพตามมาตรฐานอย่างเป็นระบบและมีความต่อเนื่อง เกิดผลลัพธ์แก่ผู้รับบริการ เจ้าหน้าที่ และประชาชน ได้รับการบริการที่มีคุณภาพมาตรฐาน มีความปลอดภัย และเกิดความพึงพอใจ
                </div>
                <div style="text-indent: 2.5em; text-align: justify; margin-bottom: 30px;">
                  ดังนั้น เพื่อให้การดำเนินงานพัฒนาคุณภาพโรงพยาบาล เป็นไปด้วยความเรียบร้อย และบรรลุเป้าหมายตามวัตถุประสงค์ โรงพยาบาลชานุมาน จึงขอแต่งตั้งคณะกรรมการดำเนินงานดังต่อไปนี้
                </div>
                
                <!-- Loop Committees -->
                <div v-for="(team, teamIndex) in allTeamsData" :key="team.id" class="mb-4" style="page-break-inside: avoid;">
                  <div class="fw-bold mb-2">
                    {{ teamIndex + 1 }}. {{ team.name.includes('ผู้จัดการ') || team.name.includes('ผู้ประสานงาน') ? team.name : 'ทีม' + team.name }}
                  </div>
                  
                  <!-- Members -->
                  <table class="table table-borderless table-sm mb-2" style="font-size: 16px; margin-left: 2em; width: 90%; color: #000;">
                    <tbody>
                      <tr v-for="(member, mIndex) in team.members" :key="member.id">
                        <td style="width: 8%; padding: 2px 0;">{{ teamIndex + 1 }}.{{ mIndex + 1 }}</td>
                        <td style="width: 50%; padding: 2px 0;">{{ member.officer_name }}</td>
                        <td style="padding: 2px 0;">{{ member.role }}</td>
                      </tr>
                    </tbody>
                  </table>
                  
                  <!-- Description -->
                  <div class="fw-bold mt-2 mb-1" style="margin-left: 1em;">มีหน้าที่</div>
                  <div style="margin-left: 2.5em; text-align: justify; white-space: pre-wrap; margin-bottom: 15px;">{{ team.description || '-' }}</div>
                </div>
                
                <!-- Signatures -->
                <div class="mt-5 text-center" style="margin-top: 50px !important; page-break-inside: avoid;">
                  <div>สั่ง ณ วันที่ .................................................</div>
                  <div style="margin-top: 50px;">
                    (นายธนากร คนเพียร)
                  </div>
                  <div>ผู้อำนวยการโรงพยาบาลชานุมาน</div>
                </div>
             </div>
          </div>
          <div class="modal-footer border-0 d-flex justify-content-between">
            <button type="button" class="btn btn-secondary rounded-pill px-4" @click="closePdfPreview">ปิด</button>
            <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold" @click="confirmGeneratePdf" :disabled="generatingPdf">
              <span v-if="generatingPdf" class="spinner-border spinner-border-sm me-2"></span>
              <i class="bi bi-file-earmark-pdf-fill me-2" v-else></i> ยืนยันและดาวน์โหลด PDF
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
import { Modal } from 'bootstrap';
import html2pdf from 'html2pdf.js';
import krutImgUrl from '../../assets/krut.png';

export default {
  name: 'QiCommittees',
  data() {
    return {
      activeTab: 'plans', // Default to plans per user preference or keep members. Set to plans since they requested it.
      committees: [],
      roles: [],
      selectedTeam: null,
      members: [],
      plans: [],
      allStaff: [],
      staffSearch: '',
      showStaffDropdown: false,
      form: {
        id: null,
        officer_name: '',
        role: 'กรรมการ'
      },
      planForm: {
        id: null,
        standard: '',
        recommendation: '',
        plan: '',
        indicator: '',
        target: '',
        responsible: '',
        monitoring_period: '',
        period_2568: '', period_2569: '', period_2570: '', period_2571: '',
        result_2568: '', result_2569: '', result_2570: '', result_2571: ''
      },
      addModalInstance: null,
      planModalInstance: null,
      editDescriptionText: '',
      savingDescription: false,
      editDescModalInstance: null,
      generatingPdf: false,
      allTeamsData: [],
      krutImg: krutImgUrl,
      pdfPreviewModalInstance: null
    };
  },
  computed: {
    filteredStaff() {
      if (!this.staffSearch) return [];
      const query = this.staffSearch.toLowerCase();
      // Only show up to 10 results to prevent UI lag
      return this.allStaff
        .filter(staff => staff.FULLNAME.toLowerCase().includes(query))
        .slice(0, 10);
    }
  },
  methods: {
    getRoleBadgeClass(role) {
      switch (role) {
        case 'ประธาน': return 'bg-danger';
        case 'รองประธาน': return 'bg-warning text-dark';
        case 'เลขานุการ': return 'bg-info text-dark';
        case 'ผู้ช่วยเลขานุการ': return 'bg-success';
        case 'กรรมการ': return 'bg-primary';
        case 'ที่ปรึกษา': return 'bg-dark';
        default: return 'bg-secondary';
      }
    },
    async fetchCommittees() {
      try {
        const res = await axios.get('/api-digital/qi/get_committees.php');
        if (res.data.status === 'success') {
          this.committees = res.data.data;
          // If we had a team selected, update its member count
          if (this.selectedTeam) {
            const updatedTeam = this.committees.find(c => c.id === this.selectedTeam.id);
            if (updatedTeam) this.selectedTeam.member_count = updatedTeam.member_count;
          }
        }
      } catch (e) {
        console.error(e);
      }
    },
    async fetchStaff() {
      try {
        const res = await axios.get('/api-hosoffice/get_all_staff.php');
        if (res.data.status === 'success') {
          this.allStaff = res.data.data;
        }
      } catch (e) {
        console.error(e);
      }
    },
    async fetchRoles() {
      try {
        const res = await axios.get('/api-digital/qi/get_roles.php');
        if (res.data.status === 'success') {
          this.roles = res.data.data;
        }
      } catch (e) {
        console.error(e);
      }
    },
    selectTeam(team) {
      this.selectedTeam = team;
      this.fetchMembers();
      this.fetchPlans();
    },
    async fetchMembers() {
      if (!this.selectedTeam) return;
      try {
        const res = await axios.get(`/api-digital/qi/get_members.php?committee_id=${this.selectedTeam.id}`);
        if (res.data.status === 'success') {
          this.members = res.data.data;
        }
      } catch (e) {
        console.error(e);
      }
    },
    async fetchPlans() {
      if (!this.selectedTeam) return;
      try {
        const res = await axios.get(`/api-digital/qi/get_plans.php?committee_id=${this.selectedTeam.id}`);
        if (res.data.status === 'success') {
          this.plans = res.data.data;
        }
      } catch (e) {
        console.error(e);
      }
    },
    openAddModal() {
      this.staffSearch = '';
      this.form.id = null;
      this.form.officer_name = '';
      if (this.roles.length > 0) {
        this.form.role = this.roles[this.roles.findIndex(r => r.name === 'กรรมการ') !== -1 ? this.roles.findIndex(r => r.name === 'กรรมการ') : 0].name;
      } else {
        this.form.role = 'กรรมการ';
      }
      this.showStaffDropdown = false;
      this.addModalInstance.show();
    },
    openEditModal(member) {
      this.form.id = member.id;
      this.form.officer_name = member.officer_name;
      this.staffSearch = member.officer_name;
      this.form.role = member.role;
      this.showStaffDropdown = false;
      this.addModalInstance.show();
    },
    closeAddModal() {
      this.addModalInstance.hide();
    },
    selectStaff(staff) {
      this.staffSearch = staff.FULLNAME;
      this.form.officer_name = staff.FULLNAME;
      this.showStaffDropdown = false;
    },
    async submitAddMember() {
      if (!this.form.officer_name) return;
      try {
        const payload = {
          committee_id: this.selectedTeam.id,
          officer_name: this.form.officer_name,
          role: this.form.role
        };
        let url = '/api-digital/qi/add_member.php';
        let successMsg = 'เพิ่มรายชื่อสำเร็จ';
        
        if (this.form.id) {
          payload.id = this.form.id;
          url = '/api-digital/qi/update_member.php';
          successMsg = 'อัปเดตบทบาทสำเร็จ';
        }
        
        const res = await axios.post(url, payload);
        if (res.data.status === 'success') {
          Swal.fire({ icon: 'success', title: successMsg, toast: true, position: 'top-end', showConfirmButton: false, timer: 1500 });
          this.closeAddModal();
          this.fetchMembers();
          if (!this.form.id) this.fetchCommittees();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
      }
    },
    openPlanModal(plan = null) {
      if (plan) {
        this.planForm = { ...plan };
      } else {
        this.planForm = {
          id: null,
          standard: '',
          recommendation: '',
          plan: '',
          indicator: '',
          target: '',
          responsible: '',
          monitoring_period: '',
          period_2568: '', period_2569: '', period_2570: '', period_2571: '',
          result_2568: '', result_2569: '', result_2570: '', result_2571: ''
        };
      }
      this.planModalInstance.show();
    },
    closePlanModal() {
      this.planModalInstance.hide();
    },
    async submitPlan() {
      try {
        const payload = { ...this.planForm, committee_id: this.selectedTeam.id };
        const res = await axios.post('/api-digital/qi/save_plan.php', payload);
        
        if (res.data.status === 'success') {
          Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', toast: true, position: 'top-end', showConfirmButton: false, timer: 1500 });
          this.closePlanModal();
          this.fetchPlans();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
      }
    },
    async deletePlan(id) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบ',
        text: 'คุณต้องการลบแผนพัฒนานี้หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ลบ',
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/qi/delete_plan.php', { id });
          if (res.data.status === 'success') {
            this.fetchPlans();
            Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', toast: true, position: 'top-end', showConfirmButton: false, timer: 1500 });
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (e) {
          console.error(e);
          Swal.fire('ข้อผิดพลาด', 'ไม่สามารถลบข้อมูลได้', 'error');
        }
      }
    },
    openEditDescriptionModal() {
      if (!this.selectedTeam) return;
      this.editDescriptionText = this.selectedTeam.description || '';
      if (!this.editDescModalInstance) {
        this.editDescModalInstance = new Modal(this.$refs.editDescriptionModal);
      }
      this.editDescModalInstance.show();
    },
    closeEditDescriptionModal() {
      if (this.editDescModalInstance) this.editDescModalInstance.hide();
    },
    async saveDescription() {
      if (!this.selectedTeam) return;
      this.savingDescription = true;
      try {
        const res = await axios.post('/api-digital/qi/update_team_description.php', {
          id: this.selectedTeam.id,
          description: this.editDescriptionText
        });
        if (res.data.status === 'success') {
          this.selectedTeam.description = this.editDescriptionText;
          Swal.fire({ icon: 'success', title: 'สำเร็จ', text: 'บันทึกบทบาทหน้าที่เรียบร้อยแล้ว', timer: 1500, showConfirmButton: false });
          this.closeEditDescriptionModal();
          this.fetchCommittees();
        } else {
          throw new Error(res.data.message);
        }
      } catch (error) {
        console.error(error);
        Swal.fire('ข้อผิดพลาด', error.message || 'ไม่สามารถบันทึกได้', 'error');
      } finally {
        this.savingDescription = false;
      }
    },
    async removeMember(id, name) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบ',
        html: `คุณต้องการนำ <b>${name}</b><br>ออกจากทีม ${this.selectedTeam.name} หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, นำออก',
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/qi/remove_member.php', { id });
          if (res.data.status === 'success') {
            this.fetchMembers();
            this.fetchCommittees();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (e) {
          console.error(e);
          Swal.fire('ข้อผิดพลาด', 'ไม่สามารถลบข้อมูลได้', 'error');
        }
      }
    },
    async generatePdf() {
      try {
        Swal.fire({
          title: 'กำลังดึงข้อมูล...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        const res = await axios.get('/api-digital/qi/get_all_teams_for_pdf.php');
        if (res.data.status !== 'success') throw new Error('ไม่สามารถดึงข้อมูลได้');
        
        this.allTeamsData = res.data.data;
        Swal.close();

        if (!this.pdfPreviewModalInstance) {
          this.pdfPreviewModalInstance = new Modal(this.$refs.pdfPreviewModal);
        }
        this.pdfPreviewModalInstance.show();
      } catch (err) {
        console.error(err);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดตัวอย่างก่อนพิมพ์ได้: ' + err.message, 'error');
      }
    },
    closePdfPreview() {
      if (this.pdfPreviewModalInstance) this.pdfPreviewModalInstance.hide();
    },
    async confirmGeneratePdf() {
      this.generatingPdf = true;
      try {
        Swal.fire({
          title: 'กำลังสร้าง PDF...',
          text: 'ระบบกำลังดาวน์โหลดไฟล์ กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        const element = document.getElementById('pdf-container');
        const opt = {
          margin:       0,
          filename:     'HA-Appointment-Order.pdf',
          image:        { type: 'jpeg', quality: 0.98 },
          html2canvas:  { scale: 2, useCORS: true },
          jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        
        await html2pdf().from(element).set(opt).save();
        
        Swal.close();
        this.closePdfPreview();
        Swal.fire({ icon: 'success', title: 'ดาวน์โหลดสำเร็จ', text: 'ไฟล์ถูกบันทึกลงในเครื่องของคุณแล้ว', timer: 1500, showConfirmButton: false });
      } catch (err) {
        console.error(err);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถสร้าง PDF ได้: ' + err.message, 'error');
      } finally {
        this.generatingPdf = false;
      }
    },
    handleClickOutside(e) {
      if (!e.target.closest('.position-relative')) {
        this.showStaffDropdown = false;
      }
    }
  },
  mounted() {
    this.addModalInstance = new Modal(this.$refs.addMemberModal);
    this.planModalInstance = new Modal(this.$refs.planModal);
    this.fetchCommittees();
    this.fetchRoles();
    this.fetchStaff();
    document.addEventListener('click', this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
  }
};
</script>

<style scoped>
.calm-bg-navy {
  background-color: #1a3e6f !important;
}
.calm-text-navy {
  color: #1a3e6f !important;
}
.calm-bg-lavender {
  background-color: #f0f0fa !important;
}
.calm-card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}
.nav-tabs .nav-link {
  border: none;
  border-bottom: 2px solid transparent;
  padding: 0.75rem 1.5rem;
}
.nav-tabs .nav-link.active {
  border-bottom: 2px solid #0d6efd;
  background-color: transparent;
}
</style>
