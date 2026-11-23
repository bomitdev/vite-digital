<template>
  <div class="structure-page">
    <div class="container-fluid py-5">
      <!-- Header -->
      <div class="text-center mb-4 animate-slide-down">
        <h2 class="fw-bold title-premium">โครงสร้างองค์กรโรงพยาบาลชานุมาน</h2>

        <div class="title-underline"></div>

        <p class="text-muted subtitle mb-3">Divisional Structure & Reporting Line</p>

        <!-- Breadcrumb Navigation -->
        <div
          v-if="singleGroup"
          class="d-inline-flex flex-wrap align-items-center justify-content-center gap-2 bg-white px-4 py-2 rounded-pill shadow-sm border mb-3 d-print-none"
        >
          <button
            type="button"
            class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold text-primary"
            @click="goToDept(null)"
          >
            <i class="bi bi-diagram-3-fill me-1"></i>ผังองค์กรทั้งหมด
          </button>
          <i class="bi bi-chevron-right text-muted small"></i>
          <button
            v-if="singleSub"
            type="button"
            class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold text-primary"
            @click="goToDept(singleGroup.id)"
          >
            {{ singleGroup.name }}
          </button>
          <span v-else class="fw-bold text-dark small">{{ singleGroup.name }}</span>
          <template v-if="singleSub">
            <i class="bi bi-chevron-right text-muted small"></i>
            <span class="fw-bold text-dark small">{{ singleSub.name }}</span>
          </template>
        </div>

        <!-- Toolbar: Search, Zoom, Print -->
        <div
          v-if="!loading && orgData && orgData.head"
          class="d-flex flex-wrap align-items-center justify-content-center gap-2 mt-2 d-print-none"
        >
          <!-- Search Box -->
          <div class="position-relative" style="min-width: 280px; max-width: 380px; flex: 1;">
            <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white border">
              <span class="input-group-text bg-white border-0 ps-3 text-muted">
                <i class="bi bi-search"></i>
              </span>
              <input
                type="text"
                class="form-control border-0 shadow-none"
                v-model="searchQuery"
                placeholder="ค้นหาชื่อบุคลากร, กลุ่มงาน, หน่วยงาน..."
              />
              <button
                v-if="searchQuery"
                type="button"
                class="btn btn-link border-0 text-muted pe-3"
                @click="clearSearch"
                title="ล้างคำค้นหา"
              >
                <i class="bi bi-x-circle-fill"></i>
              </button>
            </div>

            <!-- Search Results Dropdown -->
            <div
              v-if="searchQuery.trim()"
              class="search-dropdown shadow-lg rounded-4 bg-white border text-start mt-2 p-2"
            >
              <div v-if="searchResults.length === 0" class="text-center text-muted small py-3">
                ไม่พบข้อมูลที่ตรงกับ "{{ searchQuery }}"
              </div>
              <button
                v-for="(item, idx) in searchResults"
                :key="'sr-' + idx"
                type="button"
                class="btn btn-light w-100 text-start rounded-3 mb-1 px-3 py-2 d-flex align-items-center justify-content-between gap-2 search-item"
                @click="selectSearchResult(item)"
              >
                <div class="overflow-hidden">
                  <div class="fw-semibold text-dark text-truncate small">{{ item.title }}</div>
                  <div class="text-muted text-truncate" style="font-size: 0.72rem;">{{ item.subtitle }}</div>
                </div>
                <span class="badge rounded-pill flex-shrink-0" :class="item.badgeClass">
                  {{ item.typeLabel }}
                </span>
              </button>
            </div>
          </div>

          <!-- View Mode Switcher -->
          <div class="btn-group shadow-sm rounded-pill overflow-hidden bg-white border" role="group">
            <button
              type="button"
              class="btn btn-sm px-3 py-2 border-0 fw-semibold"
              :class="viewMode === 'chart' ? 'btn-primary text-white' : 'btn-light text-secondary'"
              @click="viewMode = 'chart'"
            >
              <i class="bi bi-diagram-3 me-1"></i> ผังองค์กร
            </button>
            <button
              type="button"
              class="btn btn-sm px-3 py-2 border-0 fw-semibold"
              :class="viewMode === 'list' ? 'btn-primary text-white' : 'btn-light text-secondary'"
              @click="viewMode = 'list'"
            >
              <i class="bi bi-list-ul me-1"></i> มุมมองรายชื่อ
            </button>
          </div>

          <!-- Zoom Controls -->
          <div
            v-if="viewMode === 'chart'"
            class="btn-group shadow-sm rounded-pill overflow-hidden bg-white border"
            role="group"
          >
            <button
              type="button"
              class="btn btn-light btn-sm px-3 border-0"
              @click="adjustZoom(-0.1)"
              :disabled="zoomLevel <= 0.6"
              title="ย่อผังองค์กร"
            >
              <i class="bi bi-zoom-out"></i>
            </button>
            <button
              type="button"
              class="btn btn-light btn-sm px-2 border-0 fw-semibold text-secondary"
              @click="zoomLevel = 1"
              title="รีเซ็ตขนาด 100%"
            >
              {{ Math.round(zoomLevel * 100) }}%
            </button>
            <button
              type="button"
              class="btn btn-light btn-sm px-3 border-0"
              @click="adjustZoom(0.1)"
              :disabled="zoomLevel >= 1.3"
              title="ขยายผังองค์กร"
            >
              <i class="bi bi-zoom-in"></i>
            </button>
          </div>

          <!-- Print Button -->
          <button
            type="button"
            class="btn btn-dark btn-sm rounded-pill px-3 py-2 shadow-sm fw-semibold"
            @click="printChart"
          >
            <i class="bi bi-printer me-1"></i> พิมพ์ผังองค์กร
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div class="chart-wrapper" v-if="loading">
        <div class="loading-container">
          <div class="spinner-border text-primary"></div>
          <p class="mt-3 text-muted">กำลังโหลดข้อมูลองค์กร...</p>
        </div>
      </div>

      <!-- Directory / List View (Easy to scan) -->
      <div class="container py-2" style="max-width: 1140px;" v-else-if="orgData && orgData.head && viewMode === 'list'">
        <!-- Director Summary Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
          <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3 bg-white">
            <div class="d-flex align-items-center gap-3">
              <img
                :src="orgData.head.image || directorFallback"
                @error="setFallback"
                class="rounded-circle border border-3 border-warning shadow-sm object-fit-cover"
                style="width: 72px; height: 72px;"
              />
              <div>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill mb-1">
                  ผู้อำนวยการโรงพยาบาลชานุมาน
                </span>
                <h5 class="fw-bold text-dark mb-0">{{ orgData.head.name }}</h5>
                <small class="text-muted">{{ orgData.head.role }}</small>
              </div>
            </div>
            <div class="d-flex gap-2 d-print-none">
              <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" @click="expandAllGroups(true)">
                <i class="bi bi-arrows-expand me-1"></i>ขยายทั้งหมด
              </button>
              <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" @click="expandAllGroups(false)">
                <i class="bi bi-arrows-collapse me-1"></i>ย่อทั้งหมด
              </button>
            </div>
          </div>
        </div>

        <!-- Groups Accordion List -->
        <div class="d-flex flex-column gap-3">
          <div
            v-for="group in (orgData.groups || [])"
            :key="'dir-' + group.id"
            class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white"
          >
            <div
              class="card-header bg-white border-0 p-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2 cursor-pointer"
              @click="toggleExpandGroup(group.id)"
            >
              <div class="d-flex align-items-center gap-3">
                <img
                  :src="group.head?.image || headFallback"
                  @error="setFallback"
                  class="rounded-circle border object-fit-cover"
                  style="width: 48px; height: 48px;"
                />
                <div>
                  <div class="fw-bold text-dark fs-6">{{ group.name }}</div>
                  <div class="small text-muted">
                    หัวหน้า: <span class="text-dark fw-semibold">{{ group.head?.name || '-' }}</span>
                    <span v-if="group.head?.role"> ({{ group.head.role }})</span>
                  </div>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1">
                  {{ getGroupStaffCount(group) }} คน
                </span>
                <span v-if="group.subs?.length" class="badge bg-purple-subtle text-purple border rounded-pill px-3 py-1">
                  {{ group.subs.length }} หน่วยงานย่อย
                </span>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-primary rounded-pill px-3 d-print-none"
                  @click.stop="viewMode = 'chart'; goToDept(group.id);"
                >
                  <i class="bi bi-diagram-3 me-1"></i>ดูแบบผัง
                </button>
                <i
                  class="bi fs-5 text-muted ms-1"
                  :class="isGroupExpanded(group.id) ? 'bi-chevron-up' : 'bi-chevron-down'"
                ></i>
              </div>
            </div>

            <div v-if="isGroupExpanded(group.id)" class="card-body border-top bg-light bg-opacity-50 p-4">
              <!-- Direct Staff -->
              <div v-if="group.staff?.length" class="mb-4">
                <div class="small fw-bold text-success mb-2">
                  <i class="bi bi-people-fill me-1"></i>บุคลากรในกลุ่มงาน ({{ group.staff.length }} คน)
                </div>
                <div class="row g-2">
                  <div class="col-12 col-sm-6 col-md-4 col-lg-3" v-for="(m, mi) in group.staff" :key="'dm-' + mi">
                    <div
                      class="d-flex align-items-center gap-2 p-2 rounded-3 bg-white border shadow-xs"
                      :class="{ 'highlight-card': isHighlighted(m.name) }"
                    >
                      <img :src="m.image || headFallback" @error="setFallback" class="rounded-circle object-fit-cover flex-shrink-0" style="width: 36px; height: 36px;" />
                      <div class="overflow-hidden">
                        <div class="fw-semibold text-dark text-truncate" style="font-size: 0.84rem;">{{ m.name }}</div>
                        <div class="text-muted text-truncate" style="font-size: 0.72rem;">{{ m.role || '-' }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Sub-departments -->
              <div v-if="group.subs?.length" class="d-flex flex-column gap-3">
                <div
                  v-for="(sub, si) in group.subs"
                  :key="'dsub-' + si"
                  class="p-3 rounded-4 bg-white border"
                >
                  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-purple text-white rounded-pill px-3 py-1">{{ sub.name }}</span>
                      <span class="small text-muted">
                        หัวหน้า: <strong class="text-dark">{{ sub.head?.name || '-' }}</strong>
                      </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-light text-secondary border rounded-pill">{{ getSubStaffCount(sub) }} คน</span>
                      <button
                        type="button"
                        class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0 d-print-none"
                        style="font-size: 0.75rem;"
                        @click="viewMode = 'chart'; goToSubDept(group.id, sub.id);"
                      >
                        ดูแบบผัง
                      </button>
                    </div>
                  </div>
                  <div v-if="sub.staff?.length" class="row g-2">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3" v-for="(sm, smi) in sub.staff" :key="'dsm-' + smi">
                      <div
                        class="d-flex align-items-center gap-2 p-2 rounded-3 bg-light border"
                        :class="{ 'highlight-card': isHighlighted(sm.name) }"
                      >
                        <img :src="sm.image || headFallback" @error="setFallback" class="rounded-circle object-fit-cover flex-shrink-0" style="width: 34px; height: 34px;" />
                        <div class="overflow-hidden">
                          <div class="fw-semibold text-dark text-truncate" style="font-size: 0.82rem;">{{ sm.name }}</div>
                          <div class="text-muted text-truncate" style="font-size: 0.7rem;">{{ sm.role || '-' }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="small text-muted">ไม่มีรายชื่อบุคลากรเพิ่มเติม</div>
                </div>
              </div>

              <div v-if="!group.staff?.length && !group.subs?.length" class="small text-muted">
                ไม่มีรายชื่อบุคลากรเพิ่มเติมในกลุ่มงานนี้
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Org Chart -->
      <div class="chart-wrapper" v-else-if="orgData && orgData.head && viewMode === 'chart'">
        <div class="stepped-tree" :style="{ zoom: zoomLevel }">
          <!-- DIRECTOR -->
          <div class="director-section">
            <div class="org-card director-card">
              <div class="avatar-wrapper large">
                <img :src="orgData.head.image || directorFallback" @error="setFallback" />
              </div>

              <div class="info">
                <h5 class="name">{{ orgData.head.name }}</h5>

                <div class="role">
                  {{ orgData.head.role }}
                </div>
              </div>
            </div>

            <div class="node-label">ผู้อำนวยการโรงพยาบาลชานุมาน</div>

            <div class="main-vertical-line"></div>
          </div>

          <!-- Main Horizontal -->
          <div class="main-horizontal-line" v-if="!singleGroup"></div>

          <!-- Drill Down Mode for Sub-Department -->
          <div class="drill-down-container" v-if="singleSub">
            <div class="zone-line"></div>

            <div class="group-node drill-down-node">
              <div
                class="badge-label"
                style="
                  font-size: 1.1rem;
                  padding: 10px 30px;
                  background: linear-gradient(135deg, #8b5cf6, #6d28d9);
                "
              >
                {{ singleSub.name }}
              </div>

              <div class="org-card group-card" style="min-width: 220px">
                <div
                  class="avatar-wrapper large"
                  style="background: linear-gradient(135deg, #8b5cf6, #6d28d9)"
                >
                  <img :src="singleSub.head.image || headFallback" @error="setFallback" />
                </div>
                <div class="info">
                  <h5 class="name">{{ singleSub.head.name }}</h5>
                  <div class="role text-muted small">หัวหน้าหน่วยงาน</div>
                </div>
              </div>

              <!-- Sub Staff -->
              <div class="staff-section" v-if="singleSub.staff?.length">
                <div class="subs-vertical-line"></div>
                <div class="badge-label staff-badge">บุคลากรในหน่วยงาน</div>
                <div class="staff-wrap-container">
                  <div
                    class="staff-node"
                    v-for="(member, idx) in singleSub.staff"
                    :key="'substaff-' + idx"
                  >
                    <div
                      class="org-card staff-card"
                      :class="{ 'highlight-card': isHighlighted(member.name) }"
                    >
                      <div class="avatar-wrapper micro">
                        <img :src="member.image || headFallback" @error="setFallback" />
                      </div>
                      <div class="info">
                        <h6 class="name">{{ member.name }}</h6>
                        <div class="role text-muted" style="font-size: 0.75rem">
                          {{ member.role }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Drill Down Mode for Main Group -->
          <div class="drill-down-container" v-else-if="singleGroup">
            <div class="zone-line"></div>

            <div class="group-node drill-down-node">
              <div class="badge-label" style="font-size: 1.1rem; padding: 10px 30px">
                {{ singleGroup.name }}
              </div>

              <div class="org-card group-card" style="min-width: 220px">
                <div class="avatar-wrapper large">
                  <img :src="singleGroup.head.image || headFallback" @error="setFallback" />
                </div>
                <div class="info">
                  <h5 class="name">{{ singleGroup.head.name }}</h5>
                </div>
              </div>

              <!-- Group Staff -->
              <div class="staff-section" v-if="singleGroup.staff?.length">
                <div class="subs-vertical-line"></div>
                <div class="badge-label staff-badge">บุคลากรในกลุ่มงาน</div>
                <div class="staff-wrap-container">
                  <div
                    class="staff-node"
                    v-for="(member, idx) in singleGroup.staff"
                    :key="'gstaff-' + idx"
                  >
                    <div
                      class="org-card staff-card"
                      :class="{ 'highlight-card': isHighlighted(member.name) }"
                    >
                      <div class="avatar-wrapper micro">
                        <img :src="member.image || headFallback" @error="setFallback" />
                      </div>
                      <div class="info">
                        <h6 class="name">{{ member.name }}</h6>
                        <div class="role text-muted" style="font-size: 0.75rem">
                          {{ member.role }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Group Subs -->
              <div class="subs-section" v-if="singleGroup.subs?.length">
                <div class="subs-vertical-line"></div>
                <div class="badge-label support-main-badge">หน่วยงานย่อย</div>
                <div class="subs-wrap-container" style="max-width: 1200px">
                  <div
                    class="sub-node-wrapped"
                    v-for="(sub, idx) in singleGroup.subs"
                    :key="'gsub-' + idx"
                  >
                    <div class="badge-label sub-badge">{{ sub.name }}</div>
                    <div
                      class="org-card sub-card clickable-card"
                      :class="{ 'highlight-card': isHighlighted(sub.name) || isHighlighted(sub.head?.name) }"
                      @click="goToSubDept(singleGroup.id, sub.id)"
                      title="คลิกเพื่อดูเฉพาะหน่วยงานนี้"
                    >
                      <div class="avatar-wrapper mini">
                        <img :src="sub.head.image || headFallback" @error="setFallback" />
                      </div>
                      <div class="info">
                        <h6 class="name">{{ sub.head.name }}</h6>
                        <div v-if="sub.head.role" class="role text-muted" style="font-size: 0.72rem">
                          {{ sub.head.role }}
                        </div>
                        <span class="badge bg-purple-subtle text-purple border rounded-pill mt-1 count-pill">
                          {{ getSubStaffCount(sub) }} คน
                        </span>
                      </div>
                    </div>

                    <!-- Sub Staff -->
                    <div
                      class="staff-wrap-container mt-3"
                      v-if="sub.staff?.length"
                      style="padding: 15px; background: transparent; border: none; box-shadow: none"
                    >
                      <div
                        class="staff-node"
                        v-for="(s_member, s_idx) in sub.staff"
                        :key="'sstaff-' + s_idx"
                      >
                        <div class="org-card staff-card micro-card">
                          <div class="avatar-wrapper micro">
                            <img :src="s_member.image || headFallback" @error="setFallback" />
                          </div>
                          <div class="info">
                            <h6 class="name" style="font-size: 0.8rem">{{ s_member.name }}</h6>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ZONES (Clean Balanced Grid) -->
          <div class="overview-zones-wrapper" v-else>
            <div class="overview-grid">
              <div
                v-for="group in (orgData.groups || [])"
                :key="'ov-' + group.id"
                class="overview-group-card org-card clickable-card"
                @click="goToDept(group.id)"
                title="คลิกเพื่อดูโครงสร้างและบุคลากรในกลุ่มงานนี้"
              >
                <div class="overview-card-header">
                  <span class="overview-group-title">{{ group.name }}</span>
                  <i class="bi bi-chevron-right text-white opacity-75"></i>
                </div>
                <div class="overview-card-body">
                  <img
                    :src="group.head?.image || headFallback"
                    @error="setFallback"
                    class="overview-avatar"
                  />
                  <div class="overview-info">
                    <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                      {{ group.head?.name || '-' }}
                    </div>
                    <div class="text-muted small" v-if="group.head?.role">
                      {{ group.head.role }}
                    </div>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                      <span class="badge bg-primary-subtle text-primary border rounded-pill count-pill">
                        <i class="bi bi-people-fill me-1"></i>{{ getGroupStaffCount(group) }} คน
                      </span>
                      <span
                        v-if="group.subs?.length"
                        class="badge bg-purple-subtle text-purple border rounded-pill count-pill"
                      >
                        <i class="bi bi-diagram-2-fill me-1"></i>{{ group.subs.length }} หน่วยงานย่อย
                      </span>
                    </div>
                  </div>
                </div>
                <!-- Sub-department quick chips (max 6 shown) -->
                <div v-if="group.subs?.length" class="overview-subs-chips" @click.stop>
                  <button
                    v-for="sub in group.subs.slice(0, 6)"
                    :key="'chip-' + sub.id"
                    type="button"
                    class="btn btn-xs btn-light border rounded-pill px-2 py-0 text-truncate sub-chip-btn"
                    @click="goToSubDept(group.id, sub.id)"
                    :title="sub.name"
                  >
                    {{ sub.name }}
                  </button>
                  <button
                    v-if="group.subs.length > 6"
                    type="button"
                    class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0 sub-chip-btn"
                    @click="goToDept(group.id)"
                  >
                    + อีก {{ group.subs.length - 6 }} หน่วยงาน
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty / Error State -->
      <div v-else class="text-center py-5">
        <div class="card border-0 shadow-sm rounded-4 p-5 mx-auto bg-white" style="max-width: 460px;">
          <i
            class="bi fs-1 mb-3 d-block"
            :class="fetchError ? 'bi-wifi-off text-danger' : 'bi-diagram-3 text-muted'"
          ></i>
          <h5 class="fw-bold text-dark mb-2">
            {{ fetchError ? 'ไม่สามารถโหลดข้อมูลโครงสร้างองค์กรได้' : 'ไม่พบข้อมูลโครงสร้างองค์กร' }}
          </h5>
          <p class="text-muted small mb-4">
            {{ fetchError ? 'กรุณาตรวจสอบการเชื่อมต่อเครือข่ายแล้วลองใหม่อีกครั้ง' : 'ยังไม่มีข้อมูลกลุ่มงานหรือบุคลากรในระบบ' }}
          </p>
          <div>
            <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" @click="fetchOrgChart">
              <i class="bi bi-arrow-clockwise me-1"></i> ลองโหลดใหม่
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

import directorFallback from '../../assets/avatars/director.png';
import headFallback from '../../assets/avatars/head.png';

const route = useRoute();
const router = useRouter();

const orgData = ref(null);
const allOrgGroups = ref([]);
const loading = ref(true);
const fetchError = ref(false);
const searchQuery = ref('');
const highlightedName = ref('');
const zoomLevel = ref(1);
const viewMode = ref('chart');
const expandedGroupIds = ref({});

const singleGroup = computed(() => {
  if (route.query.dept_id && orgData.value?.groups?.length === 1) {
    return orgData.value.groups[0];
  }
  return null;
});

const singleSub = computed(() => {
  if (route.query.sub_id && singleGroup.value) {
    return singleGroup.value.subs?.find((s) => s.id == route.query.sub_id) || null;
  }
  return null;
});

const medicalGroup = computed(() => {
  if (!orgData.value?.groups) return null;
  return orgData.value.groups.find((g) => g.name.includes('แพทย์')) || orgData.value.groups[0] || null;
});

const nursingGroup = computed(() => {
  if (!orgData.value?.groups) return null;
  const found = orgData.value.groups.find((g) => g.name.includes('พยาบาล'));
  if (found && found !== medicalGroup.value) return found;
  const fallback = orgData.value.groups.find((g) => g !== medicalGroup.value);
  return fallback || null;
});

const supportGroupsArray = computed(() => {
  if (!orgData.value?.groups) return [];

  const med = medicalGroup.value;
  const nurs = nursingGroup.value;

  return orgData.value.groups.filter((g) => g !== med && g !== nurs);
});

const searchResults = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return [];
  const sourceGroups = allOrgGroups.value.length ? allOrgGroups.value : (orgData.value?.groups || []);
  const results = [];

  sourceGroups.forEach((group) => {
    if (String(group.name || '').toLowerCase().includes(q)) {
      results.push({
        typeLabel: 'กลุ่มงาน',
        badgeClass: 'bg-primary text-white',
        title: group.name,
        subtitle: `หัวหน้า: ${group.head?.name || '-'}`,
        groupId: group.id,
        subId: null,
        highlight: group.name
      });
    }
    if (group.head?.name && String(group.head.name).toLowerCase().includes(q)) {
      results.push({
        typeLabel: 'หัวหน้ากลุ่มงาน',
        badgeClass: 'bg-info text-dark',
        title: group.head.name,
        subtitle: group.name,
        groupId: group.id,
        subId: null,
        highlight: group.head.name
      });
    }
    (group.staff || []).forEach((m) => {
      if (
        String(m.name || '').toLowerCase().includes(q) ||
        String(m.role || '').toLowerCase().includes(q)
      ) {
        results.push({
          typeLabel: 'บุคลากร',
          badgeClass: 'bg-success text-white',
          title: m.name,
          subtitle: `${group.name}${m.role ? ` • ${m.role}` : ''}`,
          groupId: group.id,
          subId: null,
          highlight: m.name
        });
      }
    });
    (group.subs || []).forEach((sub) => {
      if (String(sub.name || '').toLowerCase().includes(q)) {
        results.push({
          typeLabel: 'หน่วยงานย่อย',
          badgeClass: 'bg-purple text-white',
          title: sub.name,
          subtitle: group.name,
          groupId: group.id,
          subId: sub.id,
          highlight: sub.name
        });
      }
      if (sub.head?.name && String(sub.head.name).toLowerCase().includes(q)) {
        results.push({
          typeLabel: 'หัวหน้าหน่วยงาน',
          badgeClass: 'bg-warning text-dark',
          title: sub.head.name,
          subtitle: `${sub.name} (${group.name})`,
          groupId: group.id,
          subId: sub.id,
          highlight: sub.head.name
        });
      }
      (sub.staff || []).forEach((sm) => {
        if (
          String(sm.name || '').toLowerCase().includes(q) ||
          String(sm.role || '').toLowerCase().includes(q)
        ) {
          results.push({
            typeLabel: 'บุคลากร',
            badgeClass: 'bg-success text-white',
            title: sm.name,
            subtitle: `${sub.name}${sm.role ? ` • ${sm.role}` : ''}`,
            groupId: group.id,
            subId: sub.id,
            highlight: sm.name
          });
        }
      });
    });
  });

  return results.slice(0, 12);
});

const getSubStaffCount = (sub) => {
  if (!sub) return 0;
  return (sub.staff?.length || 0) + (sub.head?.name ? 1 : 0);
};

const getGroupStaffCount = (group) => {
  if (!group) return 0;
  const direct = group.staff?.length || 0;
  const subTotal = (group.subs || []).reduce((acc, s) => acc + getSubStaffCount(s), 0);
  return direct + subTotal + (group.head?.name ? 1 : 0);
};

const adjustZoom = (delta) => {
  const next = Math.round((zoomLevel.value + delta) * 100) / 100;
  zoomLevel.value = Math.min(1.3, Math.max(0.6, next));
};

const printChart = () => {
  window.print();
};

const toggleExpandGroup = (groupId) => {
  expandedGroupIds.value = {
    ...expandedGroupIds.value,
    [groupId]: !expandedGroupIds.value[groupId]
  };
};

const isGroupExpanded = (groupId) => Boolean(expandedGroupIds.value[groupId]);

const expandAllGroups = (expand) => {
  const next = {};
  (orgData.value?.groups || []).forEach((g) => {
    next[g.id] = expand;
  });
  expandedGroupIds.value = next;
};

const selectSearchResult = (item) => {
  highlightedName.value = item.highlight || item.title;
  searchQuery.value = '';
  if (viewMode.value === 'list') {
    expandedGroupIds.value = {
      ...expandedGroupIds.value,
      [item.groupId]: true
    };
    return;
  }
  if (item.subId) {
    goToSubDept(item.groupId, item.subId);
  } else if (item.groupId) {
    goToDept(item.groupId);
  }
};

const clearSearch = () => {
  searchQuery.value = '';
  highlightedName.value = '';
};

const isHighlighted = (name) => {
  if (!highlightedName.value || !name) return false;
  return String(name).trim() === String(highlightedName.value).trim();
};

const setFallback = (e) => {
  e.target.src = headFallback;
};

const goToDept = (deptId) => {
  if (deptId) {
    router.push({ query: { dept_id: deptId } });
  } else {
    router.push({ query: {} });
  }
};

const goToSubDept = (groupId, subId) => {
  if (groupId && subId) {
    router.push({ query: { dept_id: groupId, sub_id: subId } });
  }
};

const fetchOrgChart = async () => {
  loading.value = true;
  fetchError.value = false;

  try {
    const deptId = route.query.dept_id || '';

    const res = await axios.get(`/api-hosoffice/get_org_chart.php?dept_id=${deptId}`);

    if (res.data.status === 'success') {
      const data = res.data.data;

      // Filter out duplicate sub-nodes that have the exact same name as the parent group, but merge their staff first!
      if (data.groups && Array.isArray(data.groups)) {
        data.groups.forEach((group) => {
          if (!group.staff) group.staff = [];

          if (group.subs && Array.isArray(group.subs)) {
            // 1. Identify duplicate subs
            const duplicateSubs = group.subs.filter((sub) => sub.name === group.name);

            // 2. Merge their staff into the main group
            duplicateSubs.forEach((dupSub) => {
              if (dupSub.staff && Array.isArray(dupSub.staff)) {
                group.staff = [...group.staff, ...dupSub.staff];
              }
            });

            // 3. Remove duplicate subs
            group.subs = group.subs.filter((sub) => sub.name !== group.name);
          }

          // 4. Ensure the group head is not in the staff list
          if (group.head && group.head.name) {
            group.staff = group.staff.filter((member) => member.name !== group.head.name);
          }
        });
      }

      orgData.value = data;
      if (!deptId && Array.isArray(data.groups)) {
        allOrgGroups.value = data.groups;
      }
    } else {
      fetchError.value = true;
    }
  } catch (error) {
    console.error(error);
    fetchError.value = true;
  } finally {
    loading.value = false;
  }
};

onMounted(fetchOrgChart);

watch(() => route.query.dept_id, fetchOrgChart);
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap');

.structure-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  font-family: 'Kanit', sans-serif;
  overflow-x: auto;
  position: relative;
}

.structure-page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 400px;
  background:
    radial-gradient(circle at top right, rgba(59, 130, 246, 0.1), transparent 50%),
    radial-gradient(circle at top left, rgba(139, 92, 246, 0.1), transparent 50%);
  pointer-events: none;
}

/* HEADER */
.title-premium {
  font-size: 2.8rem;
  background: linear-gradient(90deg, #1e3a8a, #3b82f6, #8b5cf6);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  text-shadow: 0 10px 20px rgba(59, 130, 246, 0.1);
  letter-spacing: -0.5px;
}

.title-underline {
  width: 120px;
  height: 6px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6);
  border-radius: 20px;
  margin: 20px auto;
  box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
}

.subtitle {
  font-size: 1.1rem;
  color: #64748b;
  font-weight: 500;
  letter-spacing: 1px;
}

/* LOADING */
.loading-container {
  padding: 100px 0;
  text-align: center;
}

/* TREE */
.stepped-tree {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 1;
}

/* DIRECTOR */
.director-section {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.main-vertical-line {
  width: 4px;
  height: 50px;
  background: linear-gradient(to bottom, #cbd5e1, #94a3b8);
  border-radius: 4px;
}

.main-horizontal-line {
  width: 80%;
  height: 4px;
  background: #94a3b8;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* ZONES */
.zones-container {
  width: 100%;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: flex-start;
  gap: 30px;
  padding: 0 10px;
}

.zone {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  flex: 1;
  min-width: 280px;
}

.zone-line {
  width: 4px;
  height: 50px;
  background: linear-gradient(to bottom, #94a3b8, #cbd5e1);
  border-radius: 4px;
}

.subs-wrap-container,
.support-wrap-container,
.staff-wrap-container {
  width: 100%;
  max-width: 1200px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 30px;
  background: rgba(255, 255, 255, 0.4);
  border: 2px dashed #cbd5e1;
  border-radius: 20px;
  padding: 25px;
  position: relative;
  box-shadow: inset 0 4px 15px rgba(0, 0, 0, 0.02);
}

.staff-wrap-container {
  max-width: 1000px;
  gap: 15px;
}

.support-main-badge {
  background: linear-gradient(135deg, #475569, #334155);
  margin-bottom: 20px;
  font-size: 0.9rem;
  padding: 10px 24px;
}

.staff-badge {
  background: linear-gradient(135deg, #059669, #10b981);
  margin-bottom: 20px;
}

.sub-node-wrapped,
.support-node-wrapped {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  min-width: 250px;
  max-width: 320px;
}

.staff-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 180px;
}

.subs-section {
  width: 100%;
  margin-top: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.subs-vertical-line {
  width: 4px;
  height: 40px;
  background: linear-gradient(to bottom, #cbd5e1, #94a3b8);
  border-radius: 4px;
}

/* CARDS */
.org-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  border-radius: 20px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.6);
  box-shadow:
    0 10px 30px rgba(0, 0, 0, 0.06),
    0 1px 3px rgba(0, 0, 0, 0.03);
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.org-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow:
    0 20px 40px rgba(0, 0, 0, 0.1),
    0 1px 3px rgba(0, 0, 0, 0.05);
  border-color: rgba(59, 130, 246, 0.3);
  background: rgba(255, 255, 255, 0.95);
}

.director-card {
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
  color: white;
  min-width: 260px;
  border: none;
  box-shadow: 0 15px 35px rgba(37, 99, 235, 0.3);
}

.director-card:hover {
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
  box-shadow: 0 20px 45px rgba(37, 99, 235, 0.4);
}

.group-card {
  min-width: 220px;
}
.sub-card {
  min-width: 180px;
  padding: 12px;
}
.org-card.mini {
  min-width: 180px;
}

.staff-card {
  min-width: 160px;
  padding: 10px;
}
.micro-card {
  min-width: 140px;
  padding: 8px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
}

/* AVATAR */
.avatar-wrapper {
  width: 76px;
  height: 76px;
  border-radius: 50%;
  overflow: hidden;
  margin-bottom: 12px;
  padding: 3px;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
}

.org-card:hover .avatar-wrapper {
  transform: rotate(5deg) scale(1.05);
}

.avatar-wrapper.large {
  width: 96px;
  height: 96px;
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
}

.avatar-wrapper.mini {
  width: 56px;
  height: 56px;
}

.avatar-wrapper.micro {
  width: 46px;
  height: 46px;
  background: linear-gradient(135deg, #10b981, #34d399);
  margin-bottom: 8px;
}

.avatar-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
  border: 2px solid white;
}

.avatar-wrapper.clickable {
  cursor: pointer;
}
.avatar-wrapper.clickable:hover {
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

/* TEXT */
.info {
  text-align: center;
}

.name {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
  white-space: nowrap;
}

.director-card .name {
  color: white;
  font-size: 1.2rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.role {
  background: rgba(255, 255, 255, 0.2);
  padding: 5px 14px;
  border-radius: 20px;
  font-size: 0.8rem;
  margin-top: 6px;
  backdrop-filter: blur(5px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* BADGES */
.badge-label {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  padding: 8px 18px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 12px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
  letter-spacing: 0.5px;
  z-index: 2;
}

.sub-badge {
  min-height: 54px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #8b5cf6, #6d28d9);
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
}

.node-label {
  margin-top: -10px;
  margin-bottom: 15px;
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  color: #78350f;
  padding: 8px 24px;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.9rem;
  box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
  z-index: 2;
  position: relative;
}

/* ANIMATION */
.animate-slide-down {
  animation: slideDown 1s cubic-bezier(0.2, 0.8, 0.2, 1);
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* STAGGERED ENTRANCE FOR CARDS */
.stepped-tree .org-card {
  animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

.director-section .org-card {
  animation-delay: 0.1s;
}
.zone .group-card {
  animation-delay: 0.2s;
}
.support-wrap-container .group-card,
.subs-wrap-container .sub-card {
  animation-delay: 0.4s;
}
.drill-down-node > .org-card {
  animation-delay: 0.2s;
}
.staff-card {
  animation-delay: 0.4s;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.drill-down-container {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* RESPONSIVE */
@media (max-width: 992px) {
  .main-horizontal-line,
  .zone-line,
  .subs-vertical-line {
    display: none; /* Hide rigid lines on smaller screens where elements stack */
  }
  .stepped-tree {
    gap: 30px;
  }
}

@media (max-width: 768px) {
  .title-premium {
    font-size: 1.8rem;
  }
  .org-card {
    min-width: 150px;
  }
  .group-card {
    min-width: 150px;
  }
  .sub-node-wrapped,
  .support-node-wrapped {
    min-width: 140px;
  }
  .subs-wrap-container,
  .support-wrap-container {
    padding: 15px;
    gap: 15px;
  }
}

.search-dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  z-index: 1050;
  max-height: 360px;
  overflow-y: auto;
}

.search-item:hover {
  background-color: #eff6ff !important;
}

.clickable-card {
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.clickable-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.14) !important;
  border-color: #93c5fd !important;
}

.highlight-card {
  border: 2px solid #f59e0b !important;
  box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.25) !important;
  animation: highlightPulse 1.6s ease-in-out 2;
}

@keyframes highlightPulse {
  0%,
  100% {
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.25);
  }
  50% {
    box-shadow: 0 0 0 8px rgba(245, 158, 11, 0.08);
  }
}

.count-pill {
  font-size: 0.68rem;
  font-weight: 600;
  padding: 3px 8px;
}

.bg-purple {
  background-color: #7c3aed !important;
}

.bg-purple-subtle {
  background-color: #f3e8ff !important;
}

.text-purple {
  color: #6d28d9 !important;
}

.cursor-pointer {
  cursor: pointer;
}

.overview-zones-wrapper {
  width: 100%;
  max-width: 1360px;
  padding: 20px 10px 0;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
  align-items: stretch;
}

.overview-group-card {
  padding: 0 !important;
  border-radius: 18px !important;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: stretch !important;
  text-align: left !important;
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06) !important;
}

.overview-card-header {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #ffffff;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.overview-group-title {
  font-weight: 600;
  font-size: 0.95rem;
  line-height: 1.3;
}

.overview-card-body {
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.overview-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #dbeafe;
  flex-shrink: 0;
}

.overview-info {
  min-width: 0;
  flex: 1;
}

.overview-subs-chips {
  padding: 0 16px 14px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.sub-chip-btn {
  font-size: 0.72rem;
  max-width: 170px;
  border-color: #e2e8f0 !important;
}

.sub-chip-btn:hover {
  background-color: #eff6ff !important;
  border-color: #93c5fd !important;
  color: #1d4ed8 !important;
}

@media print {
  @page {
    size: A4 landscape;
    margin: 10mm;
  }
  .structure-page {
    background: #ffffff !important;
    overflow: visible !important;
  }
  .stepped-tree {
    zoom: 1 !important;
  }
}
</style>
