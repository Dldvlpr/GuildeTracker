import type { PlayerInterface } from '@/interfaces/player.interface.ts'

export interface GameGuild {
  id: string;
  name: string;
  faction: string;
  isPublic: boolean;
  showDkpPublic: boolean;
  recruitingStatus: string;
  nbrGuildMembers: number;
  nbrCharacters: number;
  players?: PlayerInterface[] | [];
}
