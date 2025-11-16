import type { RaidPlanBlock } from '@/interfaces/raidPlan.interface';

const BASE = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '');

export interface RaidPlanDTO {
  id: number;
  name: string;
  guildId: string;
  createdBy: {
    id: string;
    username: string;
  };
  blocks: RaidPlanBlock[];
  metadata?: Record<string, any> | null;
  isTemplate: boolean;
  bossId?: string | null;
  raidName?: string | null;
  isPublic?: boolean;
  shareToken?: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface CreateRaidPlanInput {
  guildId: string;
  name: string;
  blocks: RaidPlanBlock[];
  metadata?: Record<string, any>;
  isTemplate?: boolean;
  bossId?: string;
  raidName?: string;
}

export interface UpdateRaidPlanInput {
  name?: string;
  blocks?: RaidPlanBlock[];
  metadata?: Record<string, any>;
  isTemplate?: boolean;
  bossId?: string;
  raidName?: string;
}


export async function getRaidPlansByGuild(guildId: string): Promise<RaidPlanDTO[]> {
  const res = await fetch(`${BASE}/api/raid-plans/guild/${guildId}`, {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });

  if (!res.ok) {
    throw new Error(`Failed to fetch raid plans: ${res.status}`);
  }

  const data = await res.json();
  return data.plans;
}


export async function getRaidPlan(id: number): Promise<RaidPlanDTO> {
  const res = await fetch(`${BASE}/api/raid-plans/${id}`, {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });

  if (!res.ok) {
    throw new Error(`Failed to fetch raid plan: ${res.status}`);
  }

  return await res.json();
}


export async function createRaidPlan(input: CreateRaidPlanInput): Promise<RaidPlanDTO> {
  const res = await fetch(`${BASE}/api/raid-plans`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(input),
  });

  if (!res.ok) {
    throw new Error(`Failed to create raid plan: ${res.status}`);
  }

  return await res.json();
}


export async function updateRaidPlan(id: number, input: UpdateRaidPlanInput): Promise<RaidPlanDTO> {
  const res = await fetch(`${BASE}/api/raid-plans/${id}`, {
    method: 'PATCH',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(input),
  });

  if (!res.ok) {
    throw new Error(`Failed to update raid plan: ${res.status}`);
  }

  return await res.json();
}


export async function deleteRaidPlan(id: number): Promise<void> {
  const res = await fetch(`${BASE}/api/raid-plans/${id}`, {
    method: 'DELETE',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });

  if (!res.ok) {
    throw new Error(`Failed to delete raid plan: ${res.status}`);
  }
}


export async function getRaidPlanTemplates(): Promise<RaidPlanDTO[]> {
  const res = await fetch(`${BASE}/api/raid-plans/templates/public`, {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });

  if (!res.ok) {
    throw new Error(`Failed to fetch templates: ${res.status}`);
  }

  const data = await res.json();
  return data.templates;
}

export async function generateShareLink(planId: number): Promise<{ shareToken: string; shareUrl: string }>{
  const res = await fetch(`${BASE}/api/raid-plans/${planId}/share`, {
    method: 'POST',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });
  if (!res.ok) {
    throw new Error(`Failed to generate share link: ${res.status}`);
  }
  return await res.json();
}

export async function revokeShareLink(planId: number): Promise<void> {
  const res = await fetch(`${BASE}/api/raid-plans/${planId}/unshare`, {
    method: 'POST',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });
  if (!res.ok) {
    throw new Error(`Failed to revoke share link: ${res.status}`);
  }
}

export async function getPublicRaidPlan(shareToken: string) {
  const res = await fetch(`${BASE}/api/raid-plans/public/${encodeURIComponent(shareToken)}`, {
    method: 'GET',
    headers: { Accept: 'application/json' },
  });
  if (!res.ok) {
    throw new Error(`Failed to load public raid plan: ${res.status}`);
  }
  return await res.json();
}
