import type { PlayerInterface } from '@/interfaces/player.interface.ts'

export interface GameGuild {
  id: string;
  name: string;
  faction: string;
  nbrGuildMembers: string;
  players?: PlayerInterface[] | [];
}
