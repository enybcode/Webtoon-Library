export async function searchAniList(query) {
  const gql = `
    query ($search: String) {
      Page(page: 1, perPage: 10) {
        media(search: $search, type: MANGA, format_in: [MANGA, ONE_SHOT]) {
          id
          title {
            romaji
            english
          }
          description
          coverImage {
            large
          }
          chapters
          status
          genres
          staff(perPage: 3) {
            edges {
              node {
                name {
                  full
                }
              }
            }
          }
        }
      }
    }
  `;

  const response = await fetch('https://graphql.anilist.co', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({ query: gql, variables: { search: query } }),
  });

  const data = await response.json();
  return data?.data?.Page?.media || [];
}
