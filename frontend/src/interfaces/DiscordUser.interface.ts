export interface DiscordUserInterface {
  id: string;
  username: string;
  email?: string;
  avatar?: string;
  blizzardId?: string | null;
  blizzardLinked?: boolean;
}
