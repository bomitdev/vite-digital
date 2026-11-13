<template>
  <div class="container-fluid py-4 min-vh-100" style="background-color: #f4f6f9">
    <!-- Top Header -->
    <div class="row g-3 mb-4 align-items-center">
      <div class="col-12 col-md-7">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary text-white rounded-4 p-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
            <i class="bi bi-tools fs-3"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-1 text-dark">ระบบแจ้งซ่อมบำรุงทั่วไป</h4>
            <p class="text-muted small mb-0">
              งานซ่อมบำรุงอาคารสถานที่ ครุภัณฑ์ ไฟฟ้า ประปา แอร์ และสาธารณูปโภค
            </p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-5 d-flex gap-2 justify-content-md-end flex-wrap">
        <button class="btn btn-outline-secondary rounded-pill px-3 shadow-sm bg-white" @click="$router.push('/home-backoffice')">
          <i class="bi bi-arrow-left me-1"></i> กลับหน้าหลัก
        </button>
        <button v-if="canManageRepairs" class="btn btn-outline-success rounded-pill px-3 shadow-sm bg-white" @click="openDailySummaryModal">
          <i class="bi bi-bell-fill text-success me-1"></i> สรุปแจ้งเตือน MOPH
        </button>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openRequestModal()">
          <i class="bi bi-plus-lg me-1"></i> แจ้งซ่อมใหม่
        </button>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-2">
        <div
          class="card border-0 shadow-sm rounded-4 p-3 cursor-pointer transition-card h-100"
          :class="{'border-2 border-primary': filters.status === '' && !filters.myOnly}"
          @click="setStatusFilter('')"
        >
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted small fw-semibold">งานทั้งหมด</span>
            <div class="icon-circle bg-primary-subtle text-primary">
              <i class="bi bi-folder2-open"></i>
            </div>
          </div>
          <h3 class="fw-bold my-2 text-dark">{{ stats.total || 0 }}</h3>
          <span class="badge bg-light text-muted fw-normal">ประวัติทั้งหมด</span>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <div
          class="card border-0 shadow-sm rounded-4 p-3 cursor-pointer transition-card h-100"
          :class="{'border-2 border-warning': filters.status === 'Pending'}"
          @click="setStatusFilter('Pending')"
        >
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted small fw-semibold">รอดำเนินการ</span>
            <div class="icon-circle bg-warning-subtle text-warning">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>
          <h3 class="fw-bold my-2 text-warning">{{ stats.pending || 0 }}</h3>
          <span class="badge bg-warning-subtle text-warning-emphasis">รอช่างรับเรื่อง</span>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <div
          class="card border-0 shadow-sm rounded-4 p-3 cursor-pointer transition-card h-100"
          :class="{'border-2 border-primary': filters.status === 'In Progress'}"
          @click="setStatusFilter('In Progress')"
        >
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted small fw-semibold">กำลังดำเนินการ</span>
            <div class="icon-circle bg-info-subtle text-primary">
              <i class="bi bi-gear-fill"></i>
            </div>
          </div>
          <h3 class="fw-bold my-2 text-primary">{{ stats.in_progress || 0 }}</h3>
          <span class="badge bg-primary-subtle text-primary">ทีมช่างเข้าตรวจ</span>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <div
          class="card border-0 shadow-sm rounded-4 p-3 cursor-pointer transition-card h-100"
          :class="{'border-2 border-info': filters.status === 'Wait for Part'}"
          @click="setStatusFilter('Wait for Part')"
        >
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted small fw-semibold">รออะไหล่/พักงาน</span>
            <div class="icon-circle bg-info-subtle text-info">
              <i class="bi bi-pause-circle-fill"></i>
            </div>
          </div>
          <h3 class="fw-bold my-2 text-info">{{ stats.wait_part || 0 }}</h3>
          <span class="badge bg-info-subtle text-info-emphasis">สั่งซื้อ / รอของ</span>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <div
          class="card border-0 shadow-sm rounded-4 p-3 cursor-pointer transition-card h-100"
          :class="{'border-2 border-success': filters.status === 'Completed'}"
          @click="setStatusFilter('Completed')"
        >
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted small fw-semibold">ซ่อมเสร็จสิ้น</span>
            <div class="icon-circle bg-success-subtle text-success">
              <i class="bi bi-check-circle-fill"></i>
            </div>
          </div>
          <h3 class="fw-bold my-2 text-success">{{ stats.completed || 0 }}</h3>
          <span class="badge bg-success-subtle text-success">ปิดงานเรียบร้อย</span>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <div
          class="card border-0 shadow-sm rounded-4 p-3 cursor-pointer transition-card h-100"
          :class="{'border-2 border-danger': filters.status === 'Cancelled'}"
          @click="setStatusFilter('Cancelled')"
        >
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted small fw-semibold">ยกเลิกรายการ</span>
            <div class="icon-circle bg-danger-subtle text-danger">
              <i class="bi bi-x-circle-fill"></i>
            </div>
          </div>
          <h3 class="fw-bold my-2 text-danger">{{ stats.cancelled || 0 }}</h3>
          <span class="badge bg-danger-subtle text-danger">ยกเลิกคำขอ</span>
        </div>
      </div>
    </div>

    <!-- Main Content Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
      <div class="card-header bg-white py-3 border-0">
        <!-- Notice for Regular Requesters (Non-technicians / Non-admins) -->
        <div v-if="!canManageRepairs && userProfile.department" class="alert alert-info border-0 rounded-4 shadow-xs py-2 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="small">
            <i class="bi bi-info-circle-fill text-info me-2"></i>
            ระบบแสดงเฉพาะรายการแจ้งซ่อมของ <strong>{{ userProfile.fullname || 'ท่าน' }}</strong> และ <strong>กลุ่มงาน{{ userProfile.department }}</strong>
          </div>
          <span class="badge bg-white text-primary border rounded-pill px-3 py-1 small">
            <i class="bi bi-people-fill me-1"></i>กลุ่มงาน{{ userProfile.department }}
          </span>
        </div>

        <!-- Navigation Tab Pills -->
        <ul class="nav nav-pills gap-2 border-bottom pb-3">
          <li class="nav-item">
            <button
              class="nav-link rounded-pill px-3 py-2"
              :class="{ active: currentTab === 'all' }"
              @click="switchTab('all')"
            >
              <i class="bi bi-grid-3x3-gap me-1"></i> {{ canManageRepairs ? 'รายการทั้งหมด' : 'งานในกลุ่มงานทั้งหมด' }}
            </button>
          </li>
          <li class="nav-item">
            <button
              class="nav-link rounded-pill px-3 py-2"
              :class="{ active: currentTab === 'active' }"
              @click="switchTab('active')"
            >
              <i class="bi bi-activity me-1"></i> {{ canManageRepairs ? 'งานที่ต้องทำ' : 'งานที่กำลังซ่อม' }} ({{ Number(stats.pending || 0) + Number(stats.in_progress || 0) + Number(stats.wait_part || 0) }})
            </button>
          </li>
          <li class="nav-item">
            <button
              class="nav-link rounded-pill px-3 py-2"
              :class="{ active: currentTab === 'my' }"
              @click="switchTab('my')"
            >
              <i class="bi bi-person-fill me-1"></i> งานที่ฉันแจ้ง
            </button>
          </li>
          <li class="nav-item">
            <button
              class="nav-link rounded-pill px-3 py-2"
              :class="{ active: currentTab === 'completed' }"
              @click="switchTab('completed')"
            >
              <i class="bi bi-check2-all me-1"></i> ซ่อมเสร็จแล้ว ({{ stats.completed || 0 }})
            </button>
          </li>
        </ul>

        <!-- Filter Bar -->
        <div class="row g-2 pt-3 align-items-center">
          <div class="col-12 col-md-4">
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted">
                <i class="bi bi-search"></i>
              </span>
              <input
                type="text"
                v-model="filters.search"
                class="form-control bg-light border-start-0 rounded-end-pill"
                placeholder="ค้นหาเลขที่, หัวข้อ, อาการ, ผู้แจ้ง, สถานที่..."
                @input="debounceFetch"
              />
            </div>
          </div>

          <div class="col-6 col-md-2">
            <select v-model="filters.categoryId" class="form-select rounded-pill bg-light" @change="fetchRepairs">
              <option value="">-- ทุกหมวดหมู่ --</option>
              <option v-for="c in masters.categories" :key="c.id" :value="c.id">
                {{ c.name }}
              </option>
            </select>
          </div>

          <div class="col-6 col-md-2">
            <select v-model="filters.priorityId" class="form-select rounded-pill bg-light" @change="fetchRepairs">
              <option value="">-- ทุกความเร่งด่วน --</option>
              <option value="01">🟢 ปกติ</option>
              <option value="02">🟡 ด่วน</option>
              <option value="03">🟠 ด่วนมาก</option>
              <option value="04">🔴 ด่วนที่สุด</option>
            </select>
          </div>

          <div class="col-6 col-md-2">
            <select v-model="filters.techName" class="form-select rounded-pill bg-light" @change="fetchRepairs">
              <option value="">-- ทุกช่างผู้ดูแล --</option>
              <option v-for="t in masters.technicians" :key="t.id" :value="t.fullname">
                {{ t.fullname }}
              </option>
            </select>
          </div>

          <div class="col-6 col-md-2 d-flex gap-2">
            <button class="btn btn-light rounded-pill w-100 text-muted" @click="resetFilters" title="ล้างตัวกรอง">
              <i class="bi bi-arrow-counterclockwise me-1"></i> ล้างค่า
            </button>
          </div>
        </div>
      </div>

      <!-- Table Section -->
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted small mt-2">กำลังโหลดรายการแจ้งซ่อมบำรุง...</p>
        </div>

        <div v-else-if="requests.length === 0" class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
          <h6>ไม่พบข้อมูลรายการแจ้งซ่อม</h6>
          <p class="small text-muted mb-3">ลองปรับเงื่อนไขการค้นหา หรือคลิกปุ่มเพื่อแจ้งซ่อมใหม่</p>
          <button class="btn btn-outline-primary rounded-pill px-4" @click="openRequestModal()">
            <i class="bi bi-plus-lg me-1"></i> แจ้งซ่อมบำรุงใหม่
          </button>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary small">
              <tr>
                <th width="14%" class="ps-3">Ticket / วันที่แจ้ง</th>
                <th width="8%" class="text-center">รูปภาพ</th>
                <th width="28%">หัวข้อ / อาการเสีย</th>
                <th width="16%">หมวดหมู่ & สถานที่</th>
                <th width="14%">ผู้แจ้ง / ติดต่อ</th>
                <th width="10%" class="text-center">สถานะ</th>
                <th width="10%" class="text-center pe-3">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="req in requests" :key="req.id">
                <!-- Ticket No & Date -->
                <td class="ps-3">
                  <div class="fw-bold font-monospace text-dark d-flex align-items-center gap-1">
                    {{ req.ticket_no }}
                    <span v-if="req.legacy_id" class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem" title="ข้อมูลนำเข้าจากระบบเดิม (hosoffice)">
                      #{{ req.legacy_id }}
                    </span>
                  </div>
                  <div class="small text-muted mt-1" style="font-size: 0.75rem">
                    <i class="bi bi-calendar3 me-1"></i>{{ formatDateTime(req.created_at) }}
                  </div>
                  <div class="mt-1">
                    <span :class="['badge rounded-pill', getPriorityBadge(req.priority_id)]" style="font-size: 0.7rem">
                      {{ req.priority_name }}
                    </span>
                  </div>
                </td>

                <!-- Image Thumbnail -->
                <td class="text-center">
                  <div v-if="req.image_before" class="position-relative d-inline-block cursor-pointer" @click="openImageLightbox(req.image_before, req.repair_title)">
                    <img
                      :src="getImageUrl(req.image_before)"
                      class="rounded-3 border object-fit-cover shadow-sm"
                      style="width: 48px; height: 48px;"
                      alt="รูปแจ้งซ่อม"
                    />
                    <span class="badge bg-dark bg-opacity-75 position-absolute bottom-0 end-0 p-1" style="font-size: 0.6rem">
                      <i class="bi bi-zoom-in"></i>
                    </span>
                  </div>
                  <div v-else class="text-muted opacity-25">
                    <i class="bi bi-image fs-4"></i>
                  </div>
                </td>

                <!-- Title & Symptom -->
                <td>
                  <div class="fw-bold text-dark cursor-pointer text-hover-primary" @click="viewDetail(req)">
                    {{ req.repair_title }}
                  </div>
                  <div v-if="req.symptom" class="small text-muted text-truncate mt-1" style="max-width: 320px;" :title="req.symptom">
                    {{ req.symptom }}
                  </div>
                  <div v-if="req.asset_id" class="small text-primary mt-1" style="font-size: 0.75rem">
                    <i class="bi bi-upc-scan me-1"></i>ครุภัณฑ์: {{ req.asset_id }}
                  </div>
                </td>

                <!-- Category & Location -->
                <td>
                  <div class="fw-semibold text-secondary small">
                    <i class="bi bi-tag-fill me-1 text-muted"></i>{{ req.category_name || 'ทั่วไป' }}
                  </div>
                  <div class="small text-muted mt-1 text-truncate" style="max-width: 200px;" :title="req.location_name">
                    <i class="bi bi-geo-alt me-1 text-danger"></i>{{ req.location_name || '-' }}
                  </div>
                </td>

                <!-- Requester -->
                <td>
                  <div class="fw-bold text-dark small">{{ req.requester_name }}</div>
                  <div class="small text-muted" style="font-size: 0.75rem">{{ req.department_name }}</div>
                  <div v-if="req.requester_phone" class="small text-muted" style="font-size: 0.75rem">
                    <i class="bi bi-telephone-fill me-1 text-success"></i>{{ req.requester_phone }}
                  </div>
                </td>

                <!-- Status & Tech -->
                <td class="text-center">
                  <span :class="['badge rounded-pill px-3 py-1', getStatusBadge(req.status)]">
                    {{ getStatusText(req.status) }}
                  </span>
                  <div v-if="req.technician_name" class="small text-muted mt-1 text-truncate" style="max-width: 140px; font-size: 0.72rem" :title="'ช่าง: ' + req.technician_name">
                    <i class="bi bi-person-gear me-1"></i>{{ req.technician_name }}
                  </div>
                </td>

                <!-- Actions -->
                <td class="text-center pe-3">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" @click="viewDetail(req)" title="ดูรายละเอียด">
                      <i class="bi bi-eye"></i>
                    </button>
                    <!-- ปุ่มอัปเดตสถานะ (ประแจ): เข้าได้เฉพาะช่างและ Admin เท่านั้น -->
                    <button
                      v-if="canManageRepairs"
                      class="btn btn-outline-primary"
                      @click="openTechModal(req)"
                      title="จัดการงาน / ปรับสถานะ (ช่าง/Admin)"
                    >
                      <i class="bi bi-wrench"></i>
                    </button>
                    <button
                      v-if="canEditRequest(req)"
                      class="btn btn-outline-warning"
                      @click="openRequestModal(req)"
                      title="แก้ไขข้อมูล"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button
                      v-if="canDeleteRequest(req)"
                      class="btn btn-outline-danger"
                      @click="deleteRequest(req)"
                      title="ลบรายการ"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total_pages > 1" class="d-flex align-items-center justify-content-between p-3 border-top">
          <span class="small text-muted">
            แสดงหน้า {{ pagination.page }} จาก {{ pagination.total_pages }} (ทั้งหมด {{ pagination.total }} รายการ)
          </span>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: pagination.page <= 1 }">
                <button class="page-link" @click="changePage(pagination.page - 1)">ก่อนหน้า</button>
              </li>
              <li
                v-for="p in visiblePages"
                :key="p"
                class="page-item"
                :class="{ active: p === pagination.page }"
              >
                <button class="page-link" @click="changePage(p)">{{ p }}</button>
              </li>
              <li class="page-item" :class="{ disabled: pagination.page >= pagination.total_pages }">
                <button class="page-link" @click="changePage(pagination.page + 1)">ถัดไป</button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL 1: Request Repair (New / Edit)                        -->
    <!-- ============================================================ -->
    <div class="modal fade" id="requestModal" tabindex="-1" ref="requestModal">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
              <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-plus-lg" v-if="!form.id"></i>
                <i class="bi bi-pencil-square" v-else></i>
              </div>
              {{ form.id ? 'แก้ไขข้อมูลการแจ้งซ่อม' : 'แบบฟอร์มแจ้งซ่อมบำรุงทั่วไป' }}
            </h5>
            <button type="button" class="btn-close" @click="closeRequestModal"></button>
          </div>
          <div class="modal-body pt-3">
            <form @submit.prevent="submitRequest">
              <div class="row g-3">
                <!-- Requester Section -->
                <div class="col-12">
                  <div class="p-3 bg-light rounded-3">
                    <h6 class="fw-bold text-secondary mb-3 small text-uppercase">
                      <i class="bi bi-person-circle me-1"></i> ข้อมูลผู้แจ้งและหน่วยงาน
                    </h6>
                    <div class="row g-2">
                      <div class="col-md-5">
                        <label class="form-label small fw-semibold">ชื่อ-นามสกุลผู้แจ้ง <span class="text-danger">*</span></label>
                        <input
                          type="text"
                          v-model="form.requester_name"
                          class="form-control"
                          required
                          placeholder="เช่น นายสมชาย ใจดี"
                        />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small fw-semibold">แผนก / หน่วยงาน</label>
                        <input
                          type="text"
                          v-model="form.department_name"
                          class="form-control"
                          placeholder="เช่น งานอุบัติเหตุฉุกเฉิน"
                        />
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small fw-semibold">เบอร์โทรติดต่อ</label>
                        <input
                          type="text"
                          v-model="form.requester_phone"
                          class="form-control"
                          placeholder="เช่น 081-2345678 / ต่อ 101"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Problem / Task Section -->
                <div class="col-md-8">
                  <label class="form-label small fw-semibold">หัวข้อที่แจ้งซ่อม / อุปกรณ์ชำรุด <span class="text-danger">*</span></label>
                  <input
                    type="text"
                    v-model="form.repair_title"
                    class="form-control"
                    required
                    placeholder="เช่น แอร์ห้องตรวจ 1 น้ำหยดและไม่เย็น, ก๊อกน้ำอ่างล้างมือรั่ว"
                  />
                </div>

                <div class="col-md-4">
                  <label class="form-label small fw-semibold">ระดับความเร่งด่วน</label>
                  <select v-model="form.priority_id" class="form-select">
                    <option value="01">🟢 ปกติ</option>
                    <option value="02">🟡 ด่วน</option>
                    <option value="03">🟠 ด่วนมาก</option>
                    <option value="04">🔴 ด่วนที่สุด</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold">หมวดหมู่อุปกรณ์ / ประเภทงาน</label>
                  <select v-model="form.category_id" class="form-select">
                    <option value="">-- เลือกหมวดหมู่ --</option>
                    <option v-for="c in masters.categories" :key="c.id" :value="c.id">
                      {{ c.name }}
                    </option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold">สถานที่ / ตึก / ห้อง <span class="text-danger">*</span></label>
                  <input
                    list="locationList"
                    v-model="form.location_name"
                    class="form-control"
                    required
                    placeholder="พิมพ์ค้นหาหรือเลือกสถานที่..."
                  />
                  <datalist id="locationList">
                    <option v-for="loc in masters.locations" :key="loc.id" :value="loc.name"></option>
                  </datalist>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold">หมายเลขครุภัณฑ์ (ถ้ามี)</label>
                  <input
                    type="text"
                    v-model="form.asset_id"
                    class="form-control"
                    placeholder="เช่น 7440-001-0001/65"
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold">ช่างที่ต้องการระบุ (ถ้ามี)</label>
                  <select v-model="form.technician_name" class="form-select">
                    <option value="">-- ไม่ระบุ (มอบหมายตามเวร) --</option>
                    <option v-for="t in masters.technicians" :key="t.id" :value="t.fullname">
                      {{ t.fullname }} ({{ t.position || 'ช่างเทคนิค' }})
                    </option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label small fw-semibold">รายละเอียดอาการเสียเพิ่มเติม</label>
                  <textarea
                    v-model="form.symptom"
                    class="form-control"
                    rows="3"
                    placeholder="อธิบายอาการเสีย บริเวณที่พบ หรือข้อสังเกตเพิ่มเติม..."
                  ></textarea>
                </div>

                <!-- Image Upload -->
                <div class="col-12">
                  <label class="form-label small fw-semibold">แนบรูปภาพอุปกรณ์ก่อนซ่อม (ถ้ามี)</label>
                  <input
                    type="file"
                    ref="imageBeforeInput"
                    class="form-control"
                    accept="image/*"
                    @change="onImageBeforeSelected"
                  />
                  <div v-if="previewImageBefore" class="mt-2 position-relative d-inline-block">
                    <img :src="previewImageBefore" class="rounded-3 border shadow-sm" style="max-height: 120px;" />
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 rounded-circle p-1" @click="clearImageBefore">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </div>
              </div>

              <div class="modal-footer border-0 px-0 pb-0 mt-4 d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-4" @click="closeRequestModal">
                  ยกเลิก
                </button>
                <button type="submit" class="btn btn-primary rounded-pill px-4" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-send-fill me-1"></i> บันทึกและส่งแจ้งซ่อม
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL 2: Technician Status Action Modal                     -->
    <!-- ============================================================ -->
    <div class="modal fade" id="techModal" tabindex="-1" ref="techModal">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
              <i class="bi bi-wrench-adjustable text-primary"></i>
              อัปเดตสถานะงานซ่อม #{{ techForm.ticket_no }}
            </h5>
            <button type="button" class="btn-close" @click="closeTechModal"></button>
          </div>
          <div class="modal-body pt-3">
            <form @submit.prevent="submitTechUpdate">
              <div class="alert alert-light border rounded-3 p-3 mb-3">
                <div class="fw-bold text-dark">{{ techForm.repair_title }}</div>
                <div class="small text-muted">สถานที่: {{ techForm.location_name }} | ผู้แจ้ง: {{ techForm.requester_name }}</div>
              </div>

              <!-- Status Radio Group -->
              <div class="mb-3">
                <label class="form-label small fw-semibold">สถานะงานซ่อม <span class="text-danger">*</span></label>
                <div class="d-grid gap-2">
                  <div class="form-check p-2 rounded-3 border" :class="{'bg-warning-subtle border-warning': techForm.status === 'Pending'}">
                    <input class="form-check-input ms-1" type="radio" v-model="techForm.status" value="Pending" id="stPending" />
                    <label class="form-check-label fw-semibold text-warning-emphasis ms-2" for="stPending">
                      ⏳ รอดำเนินการ (Pending)
                    </label>
                  </div>
                  <div class="form-check p-2 rounded-3 border" :class="{'bg-primary-subtle border-primary': techForm.status === 'In Progress'}">
                    <input class="form-check-input ms-1" type="radio" v-model="techForm.status" value="In Progress" id="stInProg" />
                    <label class="form-check-label fw-semibold text-primary ms-2" for="stInProg">
                      ⚙️ รับงานแล้ว / กำลังดำเนินการ (In Progress)
                    </label>
                  </div>
                  <div class="form-check p-2 rounded-3 border" :class="{'bg-info-subtle border-info': techForm.status === 'Wait for Part'}">
                    <input class="form-check-input ms-1" type="radio" v-model="techForm.status" value="Wait for Part" id="stWait" />
                    <label class="form-check-label fw-semibold text-info-emphasis ms-2" for="stWait">
                      ⏸️ พักงาน / รออะไหล่ (Wait for Part)
                    </label>
                  </div>
                  <div class="form-check p-2 rounded-3 border" :class="{'bg-success-subtle border-success': techForm.status === 'Completed'}">
                    <input class="form-check-input ms-1" type="radio" v-model="techForm.status" value="Completed" id="stComp" />
                    <label class="form-check-label fw-semibold text-success ms-2" for="stComp">
                      ✅ ซ่อมเสร็จสิ้น (Completed)
                    </label>
                  </div>
                  <div class="form-check p-2 rounded-3 border" :class="{'bg-danger-subtle border-danger': techForm.status === 'Cancelled'}">
                    <input class="form-check-input ms-1" type="radio" v-model="techForm.status" value="Cancelled" id="stCancel" />
                    <label class="form-check-label fw-semibold text-danger ms-2" for="stCancel">
                      🚫 ยกเลิกรายการ (Cancelled)
                    </label>
                  </div>
                </div>
              </div>

              <!-- Tech Name -->
              <div class="mb-3">
                <label class="form-label small fw-semibold">ช่างผู้รับผิดชอบงาน</label>
                <select v-model="techForm.technician_name" class="form-select">
                  <option value="">-- เลือกช่างผู้รับผิดชอบ --</option>
                  <option v-for="t in masters.technicians" :key="t.id" :value="t.fullname">
                    {{ t.fullname }} ({{ t.position || 'ช่างเทคนิค' }})
                  </option>
                </select>
              </div>

              <!-- Comment -->
              <div class="mb-3">
                <label class="form-label small fw-semibold">บันทึกผลการซ่อม / หมายเหตุช่าง</label>
                <textarea
                  v-model="techForm.technician_comment"
                  class="form-control"
                  rows="3"
                  placeholder="เช่น เปลี่ยนลูกลอยชักโครกใหม่, ล้างแอร์และเติมน้ำยาแอร์เสร็จสิ้น ใช้งานได้ตามปกติ..."
                ></textarea>
              </div>

              <!-- Cancel Reason if Cancelled -->
              <div v-if="techForm.status === 'Cancelled'" class="mb-3">
                <label class="form-label small fw-semibold text-danger">เหตุผลการยกเลิก</label>
                <input
                  type="text"
                  v-model="techForm.cancel_reason"
                  class="form-control"
                  placeholder="ระบุเหตุผล เช่น แจ้งซ้ำ, ผู้แจ้งขอยกเลิก"
                />
              </div>

              <!-- After Image -->
              <div v-if="techForm.status === 'Completed'" class="mb-3">
                <label class="form-label small fw-semibold">แนบรูปภาพหลังซ่อมเสร็จ (ถ้ามี)</label>
                <input
                  type="file"
                  ref="imageAfterInput"
                  class="form-control"
                  accept="image/*"
                  @change="onImageAfterSelected"
                />
                <div v-if="previewImageAfter" class="mt-2">
                  <img :src="previewImageAfter" class="rounded-3 border shadow-sm" style="max-height: 100px;" />
                </div>
              </div>

              <div class="modal-footer border-0 px-0 pb-0 d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-4" @click="closeTechModal">
                  ยกเลิก
                </button>
                <button type="submit" class="btn btn-primary rounded-pill px-4" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-check-lg me-1"></i> บันทึกสถานะ
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL 3: Detail View & Timeline                             -->
    <!-- ============================================================ -->
    <div class="modal fade" id="detailModal" tabindex="-1" ref="detailModal">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
              <i class="bi bi-file-earmark-text text-primary"></i>
              รายละเอียดใบแจ้งซ่อม {{ activeDetail.ticket_no }}
            </h5>
            <button type="button" class="btn-close" @click="closeDetailModal"></button>
          </div>
          <div class="modal-body pt-3" v-if="activeDetail.id">
            <div class="row g-3">
              <div class="col-md-8">
                <div class="card bg-light border-0 rounded-4 p-3 h-100">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span :class="['badge rounded-pill', getPriorityBadge(activeDetail.priority_id)]">
                      ความเร่งด่วน: {{ activeDetail.priority_name }}
                    </span>
                    <span :class="['badge rounded-pill px-3 py-1', getStatusBadge(activeDetail.status)]">
                      {{ getStatusText(activeDetail.status) }}
                    </span>
                  </div>

                  <h5 class="fw-bold text-dark mb-2">{{ activeDetail.repair_title }}</h5>
                  <p class="text-secondary small mb-3 whitespace-pre-line" v-if="activeDetail.symptom">
                    {{ activeDetail.symptom }}
                  </p>

                  <div class="row g-2 small text-muted border-top pt-2">
                    <div class="col-6">
                      <strong>หมวดหมู่:</strong> {{ activeDetail.category_name || '-' }}
                    </div>
                    <div class="col-6">
                      <strong>สถานที่:</strong> {{ activeDetail.location_name || '-' }}
                    </div>
                    <div class="col-6" v-if="activeDetail.asset_id">
                      <strong>ครุภัณฑ์:</strong> {{ activeDetail.asset_id }}
                    </div>
                    <div class="col-6">
                      <strong>วันที่แจ้ง:</strong> {{ formatDateTime(activeDetail.created_at) }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Requester & Tech Card -->
              <div class="col-md-4">
                <div class="card bg-light border-0 rounded-4 p-3 h-100">
                  <h6 class="fw-bold text-dark mb-2 small text-uppercase">
                    <i class="bi bi-person me-1"></i> ผู้แจ้งซ่อม
                  </h6>
                  <div class="fw-bold text-dark">{{ activeDetail.requester_name }}</div>
                  <div class="small text-muted">{{ activeDetail.department_name }}</div>
                  <div class="small text-success mt-1" v-if="activeDetail.requester_phone">
                    <i class="bi bi-telephone me-1"></i>{{ activeDetail.requester_phone }}
                  </div>

                  <hr class="my-3 opacity-25" />

                  <h6 class="fw-bold text-dark mb-2 small text-uppercase">
                    <i class="bi bi-person-gear me-1"></i> ผู้รับผิดชอบงาน
                  </h6>
                  <div class="fw-bold text-primary">{{ activeDetail.technician_name || 'ยังไม่มอบหมาย' }}</div>
                  <div class="small text-muted" v-if="activeDetail.technician_comment">
                    <strong>บันทึกช่าง:</strong> {{ activeDetail.technician_comment }}
                  </div>
                  <div class="small text-success mt-1" v-if="activeDetail.completed_at">
                    <i class="bi bi-check-circle me-1"></i>เสร็จเมื่อ: {{ formatDateTime(activeDetail.completed_at) }}
                  </div>
                </div>
              </div>

              <!-- Before and After Images -->
              <div class="col-12" v-if="activeDetail.image_before || activeDetail.image_after">
                <div class="row g-3">
                  <div class="col-6" v-if="activeDetail.image_before">
                    <label class="form-label small fw-bold text-secondary">รูปภาพก่อนซ่อม</label>
                    <img
                      :src="getImageUrl(activeDetail.image_before)"
                      class="img-fluid rounded-4 border shadow-sm w-100 object-fit-cover cursor-pointer"
                      style="max-height: 240px;"
                      @click="openImageLightbox(activeDetail.image_before, 'รูปภาพก่อนซ่อม')"
                    />
                  </div>
                  <div class="col-6" v-if="activeDetail.image_after">
                    <label class="form-label small fw-bold text-success">รูปภาพหลังซ่อมเสร็จ</label>
                    <img
                      :src="getImageUrl(activeDetail.image_after)"
                      class="img-fluid rounded-4 border shadow-sm w-100 object-fit-cover cursor-pointer"
                      style="max-height: 240px;"
                      @click="openImageLightbox(activeDetail.image_after, 'รูปภาพหลังซ่อม')"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer border-0 px-0 pb-0 mt-3 d-flex justify-content-between">
              <button v-if="canManageRepairs" class="btn btn-outline-primary rounded-pill px-4" @click="openTechModal(activeDetail)">
                <i class="bi bi-wrench me-1"></i> อัปเดตสถานะงานนี้
              </button>
              <div v-else></div>
              <button type="button" class="btn btn-secondary rounded-pill px-4" @click="closeDetailModal">
                ปิดหน้าต่าง
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL 4: MOPH Daily Summary Trigger                          -->
    <!-- ============================================================ -->
    <div class="modal fade" id="summaryModal" tabindex="-1" ref="summaryModal">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
              <i class="bi bi-bell-fill text-success"></i>
              ส่งสรุปงานซ่อมบำรุงประจำวันเข้า MOPH Notify
            </h5>
            <button type="button" class="btn-close" @click="closeDailySummaryModal"></button>
          </div>
          <div class="modal-body pt-3">
            <!-- Section 1: Automatic Daily Schedule -->
            <div class="card border-0 bg-success-subtle p-3 rounded-4 mb-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="fs-5">⏰</span>
                  <strong class="text-success-emphasis">ตั้งเวลาส่งสรุปอัตโนมัติประจำวัน</strong>
                </div>
                <div class="form-check form-switch m-0">
                  <input
                    class="form-check-input cursor-pointer"
                    type="checkbox"
                    role="switch"
                    id="autoNotifySwitch"
                    v-model="notifySettings.is_enabled"
                    :true-value="1"
                    :false-value="0"
                  />
                  <label class="form-check-label small fw-semibold" for="autoNotifySwitch">
                    {{ notifySettings.is_enabled ? 'เปิดใช้งาน' : 'ปิด' }}
                  </label>
                </div>
              </div>

              <p class="small text-muted mb-2">
                ระบบจะส่งสรุปงานซ่อมบำรุง (แจ้งใหม่, กำลังซ่อม, ซ่อมเสร็จวันนี้, งานค้างสะสม) ตรงเข้ากลุ่ม LINE ทีมช่างทุกวันตามเวลาที่ระบุ
              </p>

              <div class="row g-2 align-items-center">
                <div class="col-7">
                  <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                      <i class="bi bi-clock"></i>
                    </span>
                    <input
                      type="time"
                      v-model="notifySettings.notify_time"
                      class="form-control border-start-0 fw-bold text-success text-center"
                    />
                    <span class="input-group-text bg-white">น.</span>
                  </div>
                </div>
                <div class="col-5">
                  <button
                    class="btn btn-success w-100 rounded-pill"
                    @click="saveNotifySettings"
                    :disabled="savingSettings"
                  >
                    <span v-if="savingSettings" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-check2 me-1"></i> บันทึกเวลา
                  </button>
                </div>
              </div>

              <div class="mt-2">
                <label class="form-label small fw-semibold text-secondary mb-1">
                  <i class="bi bi-link-45deg me-1"></i>URL เข้าระบบ (สำหรับแนบลิงก์ใน LINE):
                </label>
                <input
                  type="text"
                  v-model="notifySettings.app_base_url"
                  class="form-control form-control-sm rounded-pill bg-white"
                  placeholder="เช่น http://192.168.7.12:5173"
                />
              </div>

              <div class="mt-2 small text-secondary d-flex align-items-center gap-1" v-if="notifySettings.last_sent_date">
                <i class="bi bi-check-circle-fill text-success"></i>
                ส่งสรุปรอบล่าสุด: วันที่ {{ notifySettings.last_sent_date }} ({{ formatDateTime(notifySettings.last_sent_at) }})
              </div>
            </div>

            <!-- Section 2: Manual Trigger / Dry Run -->
            <div class="card border rounded-3 p-3 bg-light">
              <h6 class="fw-bold text-dark small text-uppercase mb-2">
                <i class="bi bi-send me-1"></i> ส่งแบบเลือกวัน / ทดสอบผลด้วยตนเอง
              </h6>
              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">เลือกวันที่ต้องการสรุป</label>
                <input type="date" v-model="summaryDate" class="form-control rounded-pill bg-white" />
              </div>

              <div v-if="summaryResult" class="alert alert-white border rounded-3 p-3 mb-3 shadow-sm bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="fw-bold text-dark">ผลการทดสอบ:</span>
                  <span :class="['badge rounded-pill', summaryResult.status === 'success' ? 'bg-success' : 'bg-info']">
                    {{ summaryResult.status }}
                  </span>
                </div>
                <div class="small text-muted" v-if="summaryResult.summary">
                  <div>แจ้งใหม่: <strong>{{ summaryResult.summary.new_count }}</strong> รายการ</div>
                  <div>กำลังซ่อม: <strong>{{ summaryResult.summary.in_progress_count }}</strong> รายการ</div>
                  <div>เสร็จวันนี้: <strong>{{ summaryResult.summary.completed_today_count }}</strong> รายการ</div>
                  <div>งานค้างสะสม: <strong>{{ summaryResult.summary.total_active }}</strong> รายการ</div>
                </div>
              </div>

              <div class="d-flex gap-2 justify-content-end">
                <button class="btn btn-outline-secondary rounded-pill px-3" @click="triggerSummary(true)" :disabled="submitting">
                  <i class="bi bi-eye me-1"></i> ทดสอบดูผล (Dry Run)
                </button>
                <button class="btn btn-primary rounded-pill px-4" @click="triggerSummary(false)" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-send-fill me-1"></i> ส่งเข้า MOPH ทันที
                </button>
              </div>
            </div>
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

export default {
  name: 'GeneralRepair',
  data() {
    return {
      requests: [],
      stats: {},
      masters: {
        locations: [],
        categories: [],
        service_types: [],
        technicians: [],
        staff_list: []
      },
      currentTab: 'all',
      filters: {
        search: '',
        status: '',
        categoryId: '',
        priorityId: '',
        techName: '',
        myOnly: false,
      },
      pagination: {
        page: 1,
        limit: 50,
        total: 0,
        total_pages: 1
      },
      loading: false,
      submitting: false,
      debounceTimer: null,

      // Modal instances
      reqModalInstance: null,
      techModalInstance: null,
      detailModalInstance: null,
      summaryModalInstance: null,

      // Form state
      form: {
        id: null,
        repair_title: '',
        symptom: '',
        category_id: '',
        category_name: '',
        location_id: null,
        location_name: '',
        priority_id: '01',
        requester_id: null,
        requester_name: '',
        requester_phone: '',
        department_name: '',
        asset_id: '',
        technician_name: '',
      },
      selectedImageBefore: null,
      previewImageBefore: null,

      // Tech modal state
      techForm: {
        id: null,
        ticket_no: '',
        repair_title: '',
        location_name: '',
        requester_name: '',
        status: 'In Progress',
        technician_name: '',
        technician_comment: '',
        cancel_reason: '',
      },
      selectedImageAfter: null,
      previewImageAfter: null,

      // Detail active
      activeDetail: {},

      // Daily summary modal & settings
      summaryDate: new Date().toISOString().slice(0, 10),
      summaryResult: null,
      notifySettings: {
        notify_time: '16:00',
        is_enabled: 1,
        channel_code: 'repair',
        last_sent_date: null,
        last_sent_at: null,
        last_status: null
      },
      savingSettings: false,

      // Current logged in user & permissions
      userProfile: {},
      backendPermission: null
    };
  },
  computed: {
    isAdmin() {
      if (this.backendPermission && this.backendPermission.is_admin) return true;
      if (!this.userProfile) return false;
      const access = (this.userProfile.access_user || '').split(':');
      return (
        access.includes('administrator') ||
        (this.userProfile.department && this.userProfile.department.includes('กลุ่มงานสุขภาพดิจิทัล'))
      );
    },
    isTechnician() {
      if (this.backendPermission && this.backendPermission.is_technician) return true;
      if (!this.userProfile || !this.masters.technicians) return false;
      const uid = Number(this.userProfile.id || this.userProfile.user_id || 0);
      const myName = (this.userProfile.fullname || '').trim();

      // Check by person_id or id in general_repair_technicians
      const matchId = this.masters.technicians.some(t => {
        const pid = Number(t.person_id || t.id || 0);
        return pid > 0 && pid === uid;
      });
      if (matchId) return true;

      // Check by fullname
      if (myName) {
        const cleanMy = myName.replace(/^(นาย|นางสาว|นาง|นพ\.|พญ\.|ทพ\.|ทพญ\.)\s*/, '').trim();
        return this.masters.technicians.some(t => {
          const tName = (t.fullname || '').replace(/^(นาย|นางสาว|นาง|นพ\.|พญ\.|ทพ\.|ทพญ\.)\s*/, '').trim();
          return tName && (cleanMy.includes(tName) || tName.includes(cleanMy));
        });
      }
      return false;
    },
    canManageRepairs() {
      if (this.backendPermission && typeof this.backendPermission.can_manage === 'boolean') {
        return this.backendPermission.can_manage;
      }
      return this.isAdmin || this.isTechnician;
    },
    visiblePages() {
      const current = this.pagination.page;
      const total = this.pagination.total_pages;
      const pages = [];
      const start = Math.max(1, current - 2);
      const end = Math.min(total, current + 2);
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    }
  },
  mounted() {
    this.reqModalInstance = new Modal(this.$refs.requestModal);
    this.techModalInstance = new Modal(this.$refs.techModal);
    this.detailModalInstance = new Modal(this.$refs.detailModal);
    this.summaryModalInstance = new Modal(this.$refs.summaryModal);

    // If query has ticket or search, pre-fill search filter
    if (this.$route.query) {
      if (this.$route.query.ticket || this.$route.query.search) {
        this.filters.search = this.$route.query.ticket || this.$route.query.search;
      }
      if (this.$route.query.status) {
        this.filters.status = this.$route.query.status;
      }
    }

    this.fetchMasters();
    this.fetchUserProfile().then(() => {
      this.fetchRepairs().then(() => {
        // If exact ticket opened, view detail immediately
        if (this.$route.query && this.$route.query.ticket && this.requests.length >= 1) {
          const match = this.requests.find(r => r.ticket_no === this.$route.query.ticket) || this.requests[0];
          this.viewDetail(match);
        }
      });
    });
  },
  methods: {
    async fetchUserProfile() {
      try {
        const res = await axios.get('/api-hosoffice/get_user_profile.php');
        if (res.data) {
          this.userProfile = res.data;
        }
      } catch (err) {
        console.warn('Failed to load profile:', err);
      }
    },
    async fetchMasters() {
      try {
        const res = await axios.get('/api-digital/general_repair/get_repair_masters.php');
        if (res.data && res.data.status === 'success') {
          this.masters = res.data.data;
          if (res.data.data.user_permission) {
            this.backendPermission = res.data.data.user_permission;
          }
        }
      } catch (err) {
        console.error('Failed to load masters:', err);
      }
    },
    async fetchRepairs() {
      this.loading = true;
      try {
        const params = new URLSearchParams({
          page: this.pagination.page,
          limit: this.pagination.limit,
          search: this.filters.search,
          status: this.filters.status,
          category_id: this.filters.categoryId,
          priority_id: this.filters.priorityId,
          technician_name: this.filters.techName,
          my_only: this.filters.myOnly ? '1' : '0',
          current_user: this.userProfile.fullname || '',
          current_user_id: this.userProfile.id || this.userProfile.user_id || '',
          department_id: this.userProfile.department_id || '',
          department_name: this.userProfile.department || ''
        });

        const res = await axios.get(`/api-digital/general_repair/get_repairs.php?${params.toString()}`);
        if (res.data && res.data.status === 'success') {
          this.requests = res.data.data;
          this.pagination = res.data.pagination;
          this.stats = res.data.stats || {};
          if (res.data.user_permission) {
            this.backendPermission = res.data.user_permission;
          }
        }
      } catch (err) {
        console.error('Error fetching repairs:', err);
      } finally {
        this.loading = false;
      }
    },
    debounceFetch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.pagination.page = 1;
        this.fetchRepairs();
      }, 350);
    },
    changePage(p) {
      if (p >= 1 && p <= this.pagination.total_pages) {
        this.pagination.page = p;
        this.fetchRepairs();
      }
    },
    switchTab(tab) {
      this.currentTab = tab;
      this.pagination.page = 1;
      this.filters.myOnly = false;

      if (tab === 'all') {
        this.filters.status = '';
      } else if (tab === 'active') {
        this.filters.status = 'active';
      } else if (tab === 'my') {
        this.filters.status = '';
        this.filters.myOnly = true;
      } else if (tab === 'completed') {
        this.filters.status = 'Completed';
      }
      this.fetchRepairs();
    },
    setStatusFilter(st) {
      this.filters.status = st;
      this.filters.myOnly = false;
      this.currentTab = st === '' ? 'all' : (st === 'Completed' ? 'completed' : 'all');
      this.pagination.page = 1;
      this.fetchRepairs();
    },
    resetFilters() {
      this.filters = {
        search: '',
        status: '',
        categoryId: '',
        priorityId: '',
        techName: '',
        myOnly: false,
      };
      this.currentTab = 'all';
      this.pagination.page = 1;
      this.fetchRepairs();
    },

    // Permissions Helpers
    canEditRequest(req) {
      if (this.canManageRepairs) return true;
      const uid = Number(this.userProfile.id || this.userProfile.user_id || 0);
      const myName = (this.userProfile.fullname || '').trim();
      const cleanMy = myName.replace(/^(นาย|นางสาว|นาง|นพ\.|พญ\.|ทพ\.|ทพญ\.)\s*/, '').trim();
      const reqName = (req.requester_name || '').replace(/^(นาย|นางสาว|นาง|นพ\.|พญ\.|ทพ\.|ทพญ\.)\s*/, '').trim();
      const isOwner = (uid > 0 && Number(req.requester_id) === uid) || (cleanMy && reqName.includes(cleanMy));
      return isOwner && req.status === 'Pending';
    },
    canDeleteRequest(req) {
      if (this.isAdmin) return true;
      const uid = Number(this.userProfile.id || this.userProfile.user_id || 0);
      const myName = (this.userProfile.fullname || '').trim();
      const cleanMy = myName.replace(/^(นาย|นางสาว|นาง|นพ\.|พญ\.|ทพ\.|ทพญ\.)\s*/, '').trim();
      const reqName = (req.requester_name || '').replace(/^(นาย|นางสาว|นาง|นพ\.|พญ\.|ทพ\.|ทพญ\.)\s*/, '').trim();
      const isOwner = (uid > 0 && Number(req.requester_id) === uid) || (cleanMy && reqName.includes(cleanMy));
      return isOwner && req.status === 'Pending';
    },

    // Modal Handlers
    openRequestModal(req = null) {
      if (req) {
        if (!this.canEditRequest(req)) {
          Swal.fire({
            icon: 'warning',
            title: 'ไม่มีสิทธิ์แก้ไข',
            text: 'ท่านสามารถแก้ไขได้เฉพาะรายการของตนเองที่ยังรอดำเนินการเท่านั้น'
          });
          return;
        }
        this.form = {
          id: req.id,
          repair_title: req.repair_title,
          symptom: req.symptom || '',
          category_id: req.category_id || '',
          category_name: req.category_name || '',
          location_id: req.location_id || null,
          location_name: req.location_name || '',
          priority_id: req.priority_id || '01',
          requester_id: req.requester_id || null,
          requester_name: req.requester_name || '',
          requester_phone: req.requester_phone || '',
          department_id: req.department_id || '',
          department_name: req.department_name || '',
          asset_id: req.asset_id || '',
          technician_name: req.technician_name || '',
        };
        this.previewImageBefore = req.image_before ? this.getImageUrl(req.image_before) : null;
      } else {
        this.form = {
          id: null,
          repair_title: '',
          symptom: '',
          category_id: '',
          category_name: '',
          location_id: null,
          location_name: '',
          priority_id: '01',
          requester_id: this.userProfile.id || null,
          requester_name: this.userProfile.fullname || '',
          requester_phone: this.userProfile.phone || '',
          department_id: this.userProfile.department_id || '',
          department_name: this.userProfile.department || '',
          asset_id: '',
          technician_name: '',
        };
        this.previewImageBefore = null;
      }
      this.selectedImageBefore = null;
      this.reqModalInstance.show();
    },
    closeRequestModal() {
      this.reqModalInstance.hide();
    },
    onImageBeforeSelected(e) {
      const file = e.target.files[0];
      if (file) {
        this.selectedImageBefore = file;
        this.previewImageBefore = URL.createObjectURL(file);
      }
    },
    clearImageBefore() {
      this.selectedImageBefore = null;
      this.previewImageBefore = null;
      if (this.$refs.imageBeforeInput) {
        this.$refs.imageBeforeInput.value = '';
      }
    },
    async submitRequest() {
      this.submitting = true;
      try {
        const formData = new FormData();
        Object.keys(this.form).forEach(k => {
          if (this.form[k] !== null && this.form[k] !== undefined) {
            formData.append(k, this.form[k]);
          }
        });
        if (this.selectedImageBefore) {
          formData.append('image_before', this.selectedImageBefore);
        }

        const res = await axios.post('/api-digital/general_repair/save_repair.php', formData);
        if (res.data && res.data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            text: 'ส่งเรื่องแจ้งซ่อมบำรุงและแจ้งเตือนเข้า MOPH Notify ทีมช่างเรียบร้อยแล้ว',
            timer: 2000,
            showConfirmButton: false
          });
          this.closeRequestModal();
          this.fetchRepairs();
        } else {
          throw new Error(res.data.message || 'บันทึกไม่สำเร็จ');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || err.message, 'error');
      } finally {
        this.submitting = false;
      }
    },

    // Tech Modal Handlers
    openTechModal(req) {
      if (!this.canManageRepairs) {
        Swal.fire({
          icon: 'warning',
          title: 'ไม่มีสิทธิ์เข้าถึง',
          text: 'การอัปเดตสถานะงานซ่อมสงวนสิทธิ์เฉพาะผู้ที่มีรายชื่อเป็นช่างและ Admin เท่านั้น'
        });
        return;
      }
      this.techForm = {
        id: req.id,
        ticket_no: req.ticket_no,
        repair_title: req.repair_title,
        location_name: req.location_name,
        requester_name: req.requester_name,
        status: req.status || 'In Progress',
        technician_name: req.technician_name || (this.userProfile.fullname || ''),
        technician_comment: req.technician_comment || '',
        cancel_reason: req.cancel_reason || '',
      };
      this.selectedImageAfter = null;
      this.previewImageAfter = req.image_after ? this.getImageUrl(req.image_after) : null;
      this.techModalInstance.show();
    },
    closeTechModal() {
      this.techModalInstance.hide();
    },
    onImageAfterSelected(e) {
      const file = e.target.files[0];
      if (file) {
        this.selectedImageAfter = file;
        this.previewImageAfter = URL.createObjectURL(file);
      }
    },
    async submitTechUpdate() {
      this.submitting = true;
      try {
        const formData = new FormData();
        formData.append('id', this.techForm.id);
        formData.append('status', this.techForm.status);
        formData.append('technician_name', this.techForm.technician_name);
        formData.append('technician_comment', this.techForm.technician_comment);
        formData.append('cancel_reason', this.techForm.cancel_reason);
        if (this.selectedImageAfter) {
          formData.append('image_after', this.selectedImageAfter);
        }

        const res = await axios.post('/api-digital/general_repair/update_status.php', formData);
        if (res.data && res.data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'อัปเดตสถานะสำเร็จ',
            timer: 1500,
            showConfirmButton: false
          });
          this.closeTechModal();
          this.fetchRepairs();
          if (this.activeDetail && this.activeDetail.id === this.techForm.id) {
            this.closeDetailModal();
          }
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || err.message, 'error');
      } finally {
        this.submitting = false;
      }
    },

    // Detail Modal
    viewDetail(req) {
      this.activeDetail = { ...req };
      this.detailModalInstance.show();
    },
    closeDetailModal() {
      this.detailModalInstance.hide();
    },

    // Delete Request
    async deleteRequest(req) {
      if (!this.canDeleteRequest(req)) {
        Swal.fire({
          icon: 'warning',
          title: 'ไม่มีสิทธิ์ลบรายการ',
          text: 'สามารถลบได้เฉพาะ Admin หรือผู้แจ้งเองในขณะที่ยังรอดำเนินการเท่านั้น'
        });
        return;
      }
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบรายการ?',
        text: `ต้องการลบรายการ ${req.ticket_no} (${req.repair_title}) หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'ลบรายการ',
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const formData = new FormData();
          formData.append('id', req.id);
          const res = await axios.post('/api-digital/general_repair/delete_repair.php', formData);
          if (res.data && res.data.status === 'success') {
            Swal.fire('ลบสำเร็จ', 'ลบรายการแจ้งซ่อมเรียบร้อยแล้ว', 'success');
            this.fetchRepairs();
          }
        } catch (err) {
          Swal.fire('เกิดข้อผิดพลาด', err.message, 'error');
        }
      }
    },

    // Daily Summary Modal
    openDailySummaryModal() {
      if (!this.canManageRepairs) {
        Swal.fire({
          icon: 'warning',
          title: 'ไม่มีสิทธิ์เข้าถึง',
          text: 'การสรุปแจ้งเตือน MOPH สงวนสิทธิ์เฉพาะผู้ที่มีรายชื่อเป็นช่างและ Admin เท่านั้น'
        });
        return;
      }
      this.summaryResult = null;
      this.fetchNotifySettings();
      this.summaryModalInstance.show();
    },
    closeDailySummaryModal() {
      this.summaryModalInstance.hide();
    },
    async fetchNotifySettings() {
      try {
        const res = await axios.get('/api-digital/general_repair/notify_settings.php');
        if (res.data && res.data.status === 'success') {
          this.notifySettings = res.data.data;
        }
      } catch (err) {
        console.error('Error fetching repair notify settings:', err);
      }
    },
    async saveNotifySettings() {
      this.savingSettings = true;
      try {
        const formData = new FormData();
        formData.append('notify_time', this.notifySettings.notify_time);
        formData.append('is_enabled', this.notifySettings.is_enabled ? '1' : '0');
        formData.append('channel_code', this.notifySettings.channel_code || 'repair');
        formData.append('app_base_url', this.notifySettings.app_base_url || 'http://192.168.7.12:5173');

        const res = await axios.post('/api-digital/general_repair/notify_settings.php', formData);
        if (res.data && res.data.status === 'success') {
          this.notifySettings = res.data.data;
          Swal.fire({
            icon: 'success',
            title: 'บันทึกเวลาสำเร็จ',
            text: `ตั้งเวลาส่งสรุปงานซ่อมบำรุงอัตโนมัติทุกวันเวลา ${this.notifySettings.notify_time} น. เรียบร้อยแล้ว`,
            timer: 2000,
            showConfirmButton: false
          });
        }
      } catch (err) {
        Swal.fire('เกิดข้อผิดพลาด', err.response?.data?.message || err.message, 'error');
      } finally {
        this.savingSettings = false;
      }
    },
    async triggerSummary(isDryRun) {
      this.submitting = true;
      try {
        const formData = new FormData();
        formData.append('date', this.summaryDate);
        if (isDryRun) {
          formData.append('dry_run', '1');
        }
        const res = await axios.post('/api-digital/general_repair/trigger_daily_summary.php', formData);
        this.summaryResult = res.data;
        if (!isDryRun && res.data.status === 'success') {
          Swal.fire('สำเร็จ', 'ส่งสรุปงานซ่อมบำรุงประจำวันเข้า MOPH Notify เรียบร้อยแล้ว', 'success');
        }
      } catch (err) {
        Swal.fire('เกิดข้อผิดพลาด', err.response?.data?.message || err.message, 'error');
      } finally {
        this.submitting = false;
      }
    },

    // Helper formatters
    openImageLightbox(path, title) {
      Swal.fire({
        title: title || 'รูปภาพประกอบ',
        imageUrl: this.getImageUrl(path),
        imageAlt: 'Repair Image',
        showCloseButton: true,
        showConfirmButton: false,
        width: 'auto'
      });
    },
    getImageUrl(path) {
      if (!path) return '';
      if (path.startsWith('http://') || path.startsWith('https://')) return path;
      const clean = path.replace(/^\/+/, '');
      return '/' + clean;
    },
    formatDateTime(dt) {
      if (!dt || dt === '0000-00-00 00:00:00') return '-';
      const d = new Date(dt);
      if (isNaN(d.getTime())) return dt;
      return d.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    getPriorityBadge(pId) {
      switch (pId) {
        case '04': return 'bg-danger text-white';
        case '03': return 'bg-danger-subtle text-danger border border-danger';
        case '02': return 'bg-warning-subtle text-warning-emphasis border border-warning';
        case '01':
        default: return 'bg-light text-secondary border';
      }
    },
    getStatusBadge(status) {
      switch (status) {
        case 'Pending': return 'bg-warning-subtle text-warning-emphasis border border-warning';
        case 'In Progress': return 'bg-primary text-white';
        case 'Wait for Part': return 'bg-info-subtle text-info-emphasis border border-info';
        case 'Completed': return 'bg-success text-white';
        case 'Cancelled': return 'bg-danger-subtle text-danger border border-danger';
        default: return 'bg-secondary text-white';
      }
    },
    getStatusText(status) {
      switch (status) {
        case 'Pending': return 'รอดำเนินการ';
        case 'In Progress': return 'กำลังดำเนินการ';
        case 'Wait for Part': return 'รออะไหล่/พักงาน';
        case 'Completed': return 'เสร็จสิ้น';
        case 'Cancelled': return 'ยกเลิก';
        default: return status || 'ไม่ระบุ';
      }
    }
  }
};
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.transition-card {
  transition: all 0.2s ease-in-out;
}
.transition-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
}
.icon-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
}
.text-hover-primary:hover {
  color: #0d6efd !important;
}
.whitespace-pre-line {
  white-space: pre-line;
}
</style>

