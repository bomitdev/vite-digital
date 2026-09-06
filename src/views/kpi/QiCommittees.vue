<template>
  <div class="container mt-5">
    <div class="card calm-card mb-4">
      <div class="card-header calm-bg-lavender calm-text-navy py-3 d-flex justify-content-between align-items-center border-bottom-0">
        <h4 class="mb-0 fw-bold">คณะกรรมการพัฒนาคุณภาพ (QI Teams)</h4>
        <button class="btn btn-secondary rounded-pill px-3 fw-bold" @click="$router.push('/home-backoffice')">
          <i class="bi bi-house-fill me-1"></i> กลับหน้าหลัก
        </button>
      </div>
    </div>

    <div class="row">
      <!-- Sidebar / Tabs for Committees -->
      <div class="col-md-3 mb-4">
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
      <div class="col-md-9">
        <div class="card calm-card shadow-sm h-100" v-if="selectedTeam">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="mb-0 fw-bold text-primary">รายชื่อคณะกรรมการ: {{ selectedTeam.name }}</h5>
            <button class="btn btn-success fw-bold rounded-pill px-3" @click="openAddModal">
              <i class="bi bi-person-plus-fill me-1"></i> เพิ่มรายชื่อ
            </button>
          </div>
          <div class="card-body p-0">
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
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import { Modal } from 'bootstrap';

export default {
  name: 'QiCommittees',
  data() {
    return {
      committees: [],
      roles: [],
      selectedTeam: null,
      members: [],
      allStaff: [],
      staffSearch: '',
      showStaffDropdown: false,
      form: {
        id: null,
        officer_name: '',
        role: 'กรรมการ'
      },
      addModalInstance: null
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
          // Set default role if available
          if (this.roles.length > 0) {
            this.form.role = this.roles[this.roles.findIndex(r => r.name === 'กรรมการ') !== -1 ? this.roles.findIndex(r => r.name === 'กรรมการ') : 0].name;
          }
        }
      } catch (e) {
        console.error(e);
      }
    },
    selectTeam(team) {
      this.selectedTeam = team;
      this.fetchMembers();
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
    openAddModal() {
      this.staffSearch = '';
      this.form.id = null;
      this.form.officer_name = '';
      // Set default role if available
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
          Swal.fire({
            icon: 'success',
            title: successMsg,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
          this.closeAddModal();
          this.fetchMembers();
          if (!this.form.id) this.fetchCommittees(); // Update count only when adding
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
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
            this.fetchCommittees(); // Update count
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (e) {
          console.error(e);
          Swal.fire('ข้อผิดพลาด', 'ไม่สามารถลบข้อมูลได้', 'error');
        }
      }
    },
    // Close dropdown when clicking outside
    handleClickOutside(e) {
      if (!e.target.closest('.position-relative')) {
        this.showStaffDropdown = false;
      }
    }
  },
  mounted() {
    this.addModalInstance = new Modal(this.$refs.addMemberModal);
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
</style>
