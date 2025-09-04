import type { PlayerInterface } from '@/interfaces/player.interface.ts'

export interface GameGuild {
  guildId: string;
  guildName: string;
  players: PlayerInterface[];
}
